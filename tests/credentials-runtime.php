<?php
// Offline regression: app dependencies read-only, bundle source always wins.
declare(strict_types=1);
use WebEtDesign\UserBundle\Entity\WDUser;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authentication\AuthenticatorManager;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

$loader = require ($argv[1] ?? '/app/vendor/autoload.php');
$prefix = 'WebEtDesign\\UserBundle\\';
$root = dirname(__DIR__);
$map = [];
foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root . '/src')) as $file) {
    if ($file->getExtension() === 'php') {
        $relative = substr($file->getPathname(), strlen($root . '/src/'), -4);
        $map[$prefix . str_replace('/', '\\', $relative)] = $file->getPathname();
    }
}
$loader->addClassMap($map);
$loader->setPsr4($prefix, $root . '/src');
function verify(bool $ok, string $label): void { if (!$ok) { throw new RuntimeException($label); } }
class CredentialsUser extends WDUser {
    public function getRoles(): array { return $this->getPermissions(); }
    public function getUserIdentifier(): string { return $this->getUsername(); }
}
class CredentialsChild extends CredentialsUser {
    public function __serialize(): array { return ['parent' => parent::__serialize(), 'extra' => 'child']; }
    public function __unserialize(array $data): void { parent::__unserialize($data['parent']); }
}
function fixture(): CredentialsUser {
    return (new CredentialsUser())->setUsername('fixture-user')->setEmail('fixture@example.test')->setEnabled(true)->setPassword('fixture-hash')->setPermissions(['ROLE_EDITOR'])->setPlainPassword('synthetic-transient');
}
class CredentialsAuthenticator extends AbstractAuthenticator {
    public function __construct(private CredentialsUser $user) {}
    public function supports(Request $request): ?bool { return true; }
    public function authenticate(Request $request): Passport { return new SelfValidatingPassport(new UserBadge($this->user->getUserIdentifier(), fn () => $this->user)); }
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response {
        verify($token->getUser()->getPlainPassword() === null, 'cleanup before authentication success callback');
        return new Response('ok');
    }
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response { throw new RuntimeException('unexpected authentication failure'); }
}
$cases = [
    'authentication_cleanup_without_deprecation' => function (): void {
        foreach ([false, true] as $programmatic) {
            $user = fixture();
            $dispatcher = new EventDispatcher();
            $subscriber = 'WebEtDesign\\UserBundle\\Subscriber\\CredentialsCleanupSubscriber';
            if (class_exists($subscriber)) { $dispatcher->addSubscriber(new $subscriber()); }
            $authenticator = new CredentialsAuthenticator($user);
            $storage = new TokenStorage();
            $manager = new AuthenticatorManager([$authenticator], $storage, $dispatcher, 'fixture');
            $request = Request::create('/fixture');
            $deprecations = 0;
            set_error_handler(function (int $level) use (&$deprecations): bool {
                if ($level === E_USER_DEPRECATED || $level === E_DEPRECATED) { ++$deprecations; return true; }
                return false;
            });
            try {
                if ($programmatic) { $manager->authenticateUser($user, $authenticator, $request); }
                else { verify($manager->supports($request) === true, 'manager supports'); $manager->authenticateRequest($request); }
            } finally { restore_error_handler(); }
            verify($deprecations === 0, 'authentication must emit no deprecation');
            verify($user->getPlainPassword() === null && $storage->getToken()->getUser() === $user, 'in-memory cleanup with user/token preserved');
        }
    },
    'serialization_cleanup_and_contract' => function (): void {
        foreach (['magic', 'legacy', 'native', 'child'] as $mode) {
            $user = $mode === 'child' ? (new CredentialsChild())->setPlainPassword('synthetic-transient') : fixture();
            if ($mode === 'magic') { $payload = $user->__serialize(); }
            elseif ($mode === 'legacy') { $payload = unserialize($user->serialize()); }
            else { $payload = serialize($user); }
            verify($user->getPlainPassword() === null, 'serialization clears live plainPassword: ' . $mode);
            verify(!str_contains(serialize($payload), 'synthetic-transient'), 'no plaintext in payload');
            if ($mode === 'magic' || $mode === 'legacy') { verify($payload === ['fixture-hash', true, null, 'fixture@example.test', 'fixture-user', ['ROLE_EDITOR']], 'unchanged six-field contract'); }
            if ($mode === 'native') {
                $restored = unserialize($payload);
                verify($restored->getUserIdentifier() === 'fixture-user' && $restored->getRoles() === ['ROLE_EDITOR'] && $restored->isEnabled(), 'roundtrip identity/permissions/status');
            }
        }
    },
    'deserialization_clears_existing_plaintext' => function (): void {
        foreach ([false, true] as $legacy) {
            foreach ([['fixture-hash', true, 42, 'fixture@example.test'], ['fixture-hash', true, 42, 'fixture@example.test', 'fixture-user', ['ROLE_EDITOR']]] as $data) {
                $user = fixture();
                if ($legacy) { $user->unserialize(serialize($data)); } else { $user->__unserialize($data); }
                verify($user->getPlainPassword() === null, 'deserialization clears pre-existing plaintext');
                verify($user->isEnabled() === (count($data) === 6), 'legacy session fails closed');
                verify($user->getPermissions() === (count($data) === 6 ? ['ROLE_EDITOR'] : []), 'legacy privileges cleared');
            }
        }
    },
    'hash_assignment_preserves_explicit_consumer' => function (): void {
        $user = fixture();
        $hasher = new Symfony\Component\PasswordHasher\Hasher\UserPasswordHasher(new Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactory([CredentialsUser::class => ['algorithm' => 'bcrypt', 'cost' => 4]]));
        $user->setPassword($hasher->hashPassword($user, $user->getPlainPassword()));
        verify($user->getPlainPassword() !== null, 'do not break activation mail consumer');
        verify($hasher->isPasswordValid($user, 'synthetic-transient'), 'real hash remains valid');
        $user->setPlainPassword(null);
        verify($user->getPlainPassword() === null, 'explicit post-consumer cleanup');
    },
    'legacy_erase_still_cleans' => function (): void {
        $user = fixture();
        $deprecated = 0;
        set_error_handler(function (int $level) use (&$deprecated): bool { if ($level === E_USER_DEPRECATED || $level === E_DEPRECATED) { ++$deprecated; return true; } return false; });
        try { $user->eraseCredentials(); } finally { restore_error_handler(); }
        verify($user->getPlainPassword() === null, 'legacy explicit call retains cleanup');
        verify(count((new ReflectionMethod(WDUser::class, 'eraseCredentials'))->getAttributes(Deprecated::class)) === 1, 'Symfony skip marker required');
    },
];
verify((new ReflectionClass(WDUser::class))->getFileName() === $root . '/src/Entity/WDUser.php', 'candidate source loaded');
echo 'PHP=' . PHP_VERSION . '; source=' . (new ReflectionClass(WDUser::class))->getFileName() . "\n";
$failed = 0;
foreach ($cases as $name => $case) {
    try { $case(); echo "PASS $name\n"; } catch (Throwable $e) { ++$failed; echo 'FAIL ' . $name . ': ' . $e->getMessage() . "\n"; }
}
echo "cases=" . count($cases) . "; failures=$failed; database=not-used; network=none\n";
exit($failed === 0 ? 0 : 1);
