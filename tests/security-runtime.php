<?php
// Standalone regression tests: no kernel, database, secrets or OAuth network calls.
declare(strict_types=1);

use WebEtDesign\UserBundle\Entity\WDUser;

$autoload = $argv[1] ?? dirname(__DIR__) . '/vendor/autoload.php';
if (!is_file($autoload)) { fwrite(STDERR, "Provide vendor/autoload.php\n"); exit(2); }
$loader = require $autoload;
$loader->addPsr4('WebEtDesign\\UserBundle\\', dirname(__DIR__) . '/src/', true);

class SessionUser extends WDUser
{
    public function getUserIdentifier(): string { return $this->getUsername(); }
    public function getRoles(): array { return $this->getPermissions(); }
    public function assignId(int $id): void { $this->id = $id; }
}

class WrappedSessionUser extends SessionUser
{
    private array $groups = ['ROLE_GROUP'];
    public function getRoles(): array { return array_merge(parent::getRoles(), $this->groups); }
    public function __serialize(): array { return ['parent' => parent::__serialize(), 'groups' => $this->groups, 'client' => null]; }
    public function __unserialize(array $data): void { parent::__unserialize($data['parent']); $this->groups = $data['groups']; }
}

function check(bool $condition, string $message): void
{
    if (!$condition) { throw new RuntimeException($message); }
}

function syntheticUser(): SessionUser
{
    $user = new SessionUser();
    $user->assignId(42);
    $user->setUsername('session-user')->setEmail('different@example.test')->setPassword('synthetic-not-a-real-hash')->setEnabled(true)->setPermissions(['ROLE_EDITOR', 'ROLE_AUDITOR']);
    return $user;
}

function testRouter(): Symfony\Component\Routing\Router
{
    $loader = new class extends Symfony\Component\Config\Loader\Loader {
        public function load(mixed $resource, ?string $type = null): Symfony\Component\Routing\RouteCollection {
            $routes = new Symfony\Component\Routing\RouteCollection();
            foreach (['admin_login' => '/admin/login', 'admin_login_check' => '/admin/login_check', 'sonata_admin_dashboard' => '/admin/dashboard'] as $name => $path) {
                $routes->add($name, new Symfony\Component\Routing\Route($path));
            }
            return $routes;
        }
        public function supports(mixed $resource, ?string $type = null): bool { return true; }
    };
    return new Symfony\Component\Routing\Router($loader, 'synthetic');
}

function sessionRequest(): Symfony\Component\HttpFoundation\Request
{
    $request = Symfony\Component\HttpFoundation\Request::create('/admin/login_check', 'POST', ['_username' => ' session-user ', '_password' => 'synthetic-password', '_csrf_token' => 'synthetic-csrf']);
    $request->setSession(new Symfony\Component\HttpFoundation\Session\Session(new Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage()));
    return $request;
}

function formAuthenticator(): WebEtDesign\UserBundle\Security\AdminFormLoginAuthenticator
{
    $provider = new class implements Symfony\Component\Security\Core\User\UserProviderInterface, Symfony\Component\Security\Core\User\PasswordUpgraderInterface {
        public function loadUserByIdentifier(string $identifier): Symfony\Component\Security\Core\User\UserInterface { return syntheticUser(); }
        public function refreshUser(Symfony\Component\Security\Core\User\UserInterface $user): Symfony\Component\Security\Core\User\UserInterface { return $user; }
        public function supportsClass(string $class): bool { return $class === SessionUser::class; }
        public function upgradePassword(Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void { throw new LogicException('Not exercised'); }
    };
    $router = testRouter();
    return new WebEtDesign\UserBundle\Security\AdminFormLoginAuthenticator(new Symfony\Component\Security\Http\HttpUtils($router, $router), $provider, $router, new Symfony\Component\DependencyInjection\ParameterBag\ParameterBag());
}

class LoginControllerProbe extends WebEtDesign\UserBundle\Controller\Admin\SecurityController
{
    public array $rendered = [];
    protected function getUser(): ?Symfony\Component\Security\Core\User\UserInterface { return null; }
    protected function render(string $view, array $parameters = [], ?Symfony\Component\HttpFoundation\Response $response = null): Symfony\Component\HttpFoundation\Response {
        $this->rendered = $parameters;
        return new Symfony\Component\HttpFoundation\Response('synthetic render');
    }
}

$cases = [
    'password_and_csrf_listeners' => function (): void {
        $authenticator = formAuthenticator();
        $request = sessionRequest();
        $stack = new Symfony\Component\HttpFoundation\RequestStack();
        $stack->push($request);
        $manager = new Symfony\Component\Security\Csrf\CsrfTokenManager(null, new Symfony\Component\Security\Csrf\TokenStorage\SessionTokenStorage($stack));
        $request->request->set('_csrf_token', $manager->getToken('authenticate')->getValue());
        $passport = $authenticator->authenticate($request);
        $factory = new Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactory([SessionUser::class => ['algorithm' => 'bcrypt', 'cost' => 4]]);
        $passport->getUser()->setPassword($factory->getPasswordHasher($passport->getUser())->hash('synthetic-password'));
        $event = new Symfony\Component\Security\Http\Event\CheckPassportEvent($authenticator, $passport);
        (new Symfony\Component\Security\Http\EventListener\CheckCredentialsListener($factory))->checkPassport($event);
        (new Symfony\Component\Security\Http\EventListener\CsrfProtectionListener($manager))->checkPassport($event);
        check($passport->getBadge(Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials::class)->isResolved(), 'Real hasher accepts synthetic valid password');
        check($passport->getBadge(Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge::class)->isResolved(), 'Real CSRF manager accepts valid token');
        $request->request->set('_csrf_token', 'invalid-synthetic-token');
        $bad = $authenticator->authenticate($request);
        try {
            (new Symfony\Component\Security\Http\EventListener\CsrfProtectionListener($manager))->checkPassport(new Symfony\Component\Security\Http\Event\CheckPassportEvent($authenticator, $bad));
            throw new RuntimeException('Invalid CSRF must be rejected');
        } catch (Symfony\Component\Security\Core\Exception\InvalidCsrfTokenException) {}
        $request->request->set('_password', 'wrong-synthetic-password');
        $bad = $authenticator->authenticate($request);
        $bad->getUser()->setPassword($passport->getUser()->getPassword());
        try {
            (new Symfony\Component\Security\Http\EventListener\CheckCredentialsListener($factory))->checkPassport(new Symfony\Component\Security\Http\Event\CheckPassportEvent($authenticator, $bad));
            throw new RuntimeException('Invalid password must be rejected');
        } catch (Symfony\Component\Security\Core\Exception\BadCredentialsException) {}
    },
    'child_parent_serialization_contract' => function (): void {
        $user = new WrappedSessionUser();
        $user->setUsername('child-user')->setEmail('different@example.test')->setPermissions(['ROLE_EDITOR'])->setEnabled(true);
        $restored = unserialize(serialize($user));
        check($restored->getUserIdentifier() === 'child-user' && $restored->getRoles() === ['ROLE_EDITOR', 'ROLE_GROUP'], 'Child magic envelope preserves parent identifier and direct/group roles');
        $legacy = new SessionUser();
        $legacy->unserialize($user->serialize());
        check($legacy->getUserIdentifier() === 'child-user' && $legacy->getPermissions() === ['ROLE_EDITOR'], 'Inherited legacy parent serialization must remain a parent slice, not call child magic envelope');
    },
    'nullable_session_fields' => function (): void {
        $user = new SessionUser();
        $user->setUsername('disabled-user')->setEmail(null)->setPassword(null)->setEnabled(false);
        $restored = unserialize(serialize($user));
        check($restored->getUserIdentifier() === 'disabled-user' && !$restored->isEnabled() && $restored->getPassword() === null && $restored->getEmail() === null && $restored->getId() === null && $restored->getPermissions() === [], 'Nullable fields and disabled account preserved');
    },
    'azure_selection_preserves_redirect' => function (): void {
        $client = new class extends KnpU\OAuth2ClientBundle\Client\OAuth2Client {
            public array $arguments = [];
            public function __construct() {} // Offline redirect boundary, no OAuth provider.
            public function redirect(array $scopes = [], array $options = []) {
                $this->arguments = [$scopes, $options];
                return new Symfony\Component\HttpFoundation\RedirectResponse('https://azure.example.test/authorize');
            }
        };
        $container = new Symfony\Component\DependencyInjection\Container();
        $container->set('selected.azure', $client);
        $registry = new KnpU\OAuth2ClientBundle\Client\ClientRegistry($container, ['selected' => 'selected.azure']);
        foreach ([true, false] as $enabled) {
            $request = sessionRequest();
            // Preserve existing unanchored substring semantics; do not change domain policy here.
            $request->request->set('_username', 'person@corporate.test.extra');
            $stack = new Symfony\Component\HttpFoundation\RequestStack();
            $stack->push($request);
            $controller = new LoginControllerProbe();
            $client->arguments = [];
            $configs = [['enabled' => true, 'client_name' => 'unavailable', 'domains' => ['@other.test']], ['enabled' => $enabled, 'client_name' => 'selected', 'domains' => ['@corporate.test']]];
            $response = $controller->login($request, testRouter(), new Symfony\Component\Security\Http\Authentication\AuthenticationUtils($stack), $registry, new Symfony\Component\DependencyInjection\ParameterBag\ParameterBag(['wd_user.azure.clients' => $configs]));
            if ($enabled) {
                check($response->getTargetUrl() === 'https://azure.example.test/authorize', 'Matching enabled Azure redirect');
                check($client->arguments === [['openid', 'email', 'profile'], ['login_hint' => 'person@corporate.test.extra', 'client_name' => 'selected']], 'Azure scopes/hint/client preserved');
            } else {
                check($client->arguments === [] && $controller->rendered['with_password'] === true, 'Disabled Azure must not redirect');
            }
        }
    },
    'bundle_build_no_deprecation' => function (): void {
        $deprecations = [];
        Symfony\Component\ErrorHandler\DebugClassLoader::enable();
        set_error_handler(function (int $level, string $message) use (&$deprecations): bool {
            if ($level === E_USER_DEPRECATED) { $deprecations[] = $message; return true; }
            return false;
        });
        try { $bundle = new WebEtDesign\UserBundle\WDUserBundle(); }
        finally { restore_error_handler(); }
        $buildDeprecations = array_filter($deprecations, fn(string $message) => str_contains($message, 'WDUserBundle') && str_contains($message, 'build()'));
        check($buildDeprecations === [], 'Real DebugClassLoader build deprecation: ' . implode('; ', $buildDeprecations));
        $builder = new Symfony\Component\DependencyInjection\ContainerBuilder();
        check($bundle->build($builder) === null, 'Build remains a no-op');
    },
    'local_login_does_not_create_azure_client' => function (): void {
        $request = sessionRequest();
        $request->request->set('_username', 'local@example.test');
        $stack = new Symfony\Component\HttpFoundation\RequestStack();
        $stack->push($request);
        // A real lazy service factory reproduces the missing route during client construction.
        $container = new class extends Symfony\Component\DependencyInjection\Container {
            public int $calls = 0;
            public function get(string $id, int $invalidBehavior = 1): ?object {
                ++$this->calls;
                throw new Symfony\Component\Routing\Exception\RouteNotFoundException('admin_azure_connect missing');
            }
        };
        $registry = new KnpU\OAuth2ClientBundle\Client\ClientRegistry($container, ['azure' => 'lazy.azure']);
        $controller = new LoginControllerProbe();
        $response = $controller->login($request, testRouter(), new Symfony\Component\Security\Http\Authentication\AuthenticationUtils($stack), $registry, new Symfony\Component\DependencyInjection\ParameterBag\ParameterBag(['wd_user.azure.clients' => [['enabled' => true, 'client_name' => 'azure', 'domains' => ['@corporate.test']]]]));
        check($container->calls === 0, 'Local login must not construct Azure client');
        check($response->getStatusCode() === 200 && $controller->rendered['with_password'] === true && $controller->rendered['action'] === '/admin/login_check', 'Local login password form retained');
    },
    'form_failure_session' => function (): void {
        $request = sessionRequest();
        $error = new Symfony\Component\Security\Core\Exception\BadCredentialsException('Synthetic failure');
        $response = formAuthenticator()->onAuthenticationFailure($request, $error);
        check($request->getSession()->get(Symfony\Component\Security\Http\SecurityRequestAttributes::AUTHENTICATION_ERROR) === $error, 'Failure stored for AuthenticationUtils');
        check($request->getSession()->get('_security.error_context') === 'form_login', 'Password form context retained');
        check($response->getTargetUrl() === '/admin/login', 'Failure redirects to login');
    },
    'azure_failure_session' => function (): void {
        // No constructor collaborators are needed by this isolated failure path except HttpUtils.
        $reflector = new ReflectionClass(WebEtDesign\UserBundle\Security\AdminAzureLoginAuthenticator::class);
        $authenticator = $reflector->newInstanceWithoutConstructor();
        $router = testRouter();
        $reflector->getProperty('httpUtils')->setValue($authenticator, new Symfony\Component\Security\Http\HttpUtils($router, $router));
        $request = sessionRequest();
        $error = new Symfony\Component\Security\Core\Exception\BadCredentialsException('Synthetic Azure failure');
        $response = $authenticator->onAuthenticationFailure($request, $error);
        check($request->getSession()->get(Symfony\Component\Security\Http\SecurityRequestAttributes::AUTHENTICATION_ERROR) === $error, 'Azure failure stored');
        check(str_ends_with($response->getTargetUrl(), '/admin/login'), 'Azure failure redirects to login');
    },
    'form_credentials' => function (): void {
        $request = sessionRequest();
        $passport = formAuthenticator()->authenticate($request);
        check($request->getSession()->get(Symfony\Component\Security\Http\SecurityRequestAttributes::LAST_USERNAME) === 'session-user', 'Last username must be stored');
        check($passport->getUser()->getUserIdentifier() === 'session-user', 'Valid credentials load user');
        check($passport->getBadge(Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials::class)->getPassword() === 'synthetic-password', 'Password credentials retained');
        $csrf = $passport->getBadge(Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge::class);
        check($csrf !== null && !$csrf->isResolved() && $csrf->getCsrfTokenId() === 'authenticate' && $csrf->getCsrfToken() === 'synthetic-csrf', 'CSRF must remain required/unresolved');
        check($passport->hasBadge(WebEtDesign\UserBundle\Security\Passport\LoginAttemptBadge::class), 'Throttle badge retained');
        check($passport->hasBadge(Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge::class), 'Remember-me retained');
        check($passport->getBadge(Symfony\Component\Security\Http\Authenticator\Passport\Badge\PasswordUpgradeBadge::class)->getAndErasePlaintextPassword() === 'synthetic-password', 'Password upgrade retained');
    },
    'legacy_session_fail_closed' => function (): void {
        $old = ['synthetic-not-a-real-hash', true, 42, 'different@example.test'];
        foreach (['magic', 'legacy'] as $mode) {
            $user = syntheticUser(); // Must clear stale identity/permissions on existing instances too.
            if ($mode === 'magic') { $user->__unserialize($old); }
            else { $user->unserialize(serialize($old)); }
            check(!$user->isEnabled(), 'Legacy session without identifier must be disabled');
            check($user->getUserIdentifier() === '', 'Legacy session must not invent an identifier');
            check($user->getPermissions() === [], 'Legacy session must not retain privileges');
            check($user->getId() === 42 && $user->getEmail() === $old[3] && $user->getPassword() === $old[0], 'Legacy non-identity fields');
        }
    },
    'session_roundtrip' => function (): void {
        $user = syntheticUser();
        $magic = unserialize(serialize($user));
        $legacy = new SessionUser();
        $legacy->unserialize($user->serialize());
        $payload = $user->serialize();
        $class = SessionUser::class;
        $nativeLegacy = unserialize(sprintf('C:%d:"%s":%d:{%s}', strlen($class), $class, strlen($payload), $payload));
        foreach ([$magic, $legacy, $nativeLegacy] as $restored) {
            check($user->getUserIdentifier() === $restored->getUserIdentifier(), 'Session must preserve username, not substitute email');
            check($user->getRoles() === $restored->getRoles(), 'Session must preserve permissions/roles');
            check($user->getEmail() === $restored->getEmail(), 'Session email');
            check($user->getPassword() === $restored->getPassword(), 'Session password');
            check($user->isEnabled() === $restored->isEnabled(), 'Session enabled');
            check($user->getId() === $restored->getId(), 'Session ID');
        }
    },
];

$failed = 0;
$selected = $argv[2] ?? null;
if ($selected !== null && !isset($cases[$selected])) { fwrite(STDERR, "Unknown test\n"); exit(2); }
foreach ($cases as $name => $case) {
    if ($selected !== null && $selected !== $name) { continue; }
    try { $case(); echo "PASS $name\n"; }
    catch (Throwable $e) { ++$failed; fwrite(STDERR, "FAIL $name: " . $e->getMessage() . "\n"); }
}
echo "failures=$failed; database=not-used; oauth_network=not-used\n";
exit($failed === 0 ? 0 : 1);
