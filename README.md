# WDUserBundle

Ajouter fichier de configuration dans config > packages > webetdesign > wd_cms > wd_user.yaml

```
wd_user:
  user:
    class: App\Entity\User\User (valeur par défault)
  group:
    class: App\Entity\User\Group (valeur par défault)
```

Ajouter fichier de configuration dans config > routes > wd_user.yaml
```
wd_user:
  resource: "@WDUserBundle/Resources/config/routing.yaml"
```
Intégration d'une page de connexion autre que l'admin
```
web_et_design_cms:
  pages:
    account_section:
      label: '▰▰ Rubrique utilisateur'
      template: pages/section.html.twig
      disableRoute: true
    login: # Utilisée dans sécurity controller pour définir une page et get les knp par exemple
      label: 'Page de connexion'
      route: login_page
    reset_password_request:
      label: 'Demande de réinitialisation de mot de passe'
      controller: WebEtDesign\UserBundle\Controller\User\ResettingController
      action: request
      route: !php/const WebEtDesign\UserBundle\Controller\User\ResettingController::ROUTE_RESETTING_REQUEST
      methods: [ 'GET', 'POST' ]
      template: '@WDUserBundle/Resources/views/resetting_request.html.twig'
    reset_password:
      label: 'Réinitialisation de mot de passe oublié'
      controller: WebEtDesign\UserBundle\Controller\User\ResettingController
      action: resetting
      route: !php/const WebEtDesign\UserBundle\Controller\User\ResettingController::ROUTE_RESETTING
      methods: [ 'GET', 'POST' ]
      template: '@WDUserBundle/Resources/views/resetting.html.twig'
      params:
        token:
          default: null
          requirement: null
```
Modifier le sonata_admin pour : 
```
sonata_admin:
    ...
    dashboard:
        groups:
            ...
            user:
                label: Utilisateurs
                icon: '<i class="fa fa-user"></i>'
                items:
                    - wd.user.admin.user
                    - wd.user.admin.group
```

Require dans l'application :<br>
    - entity User<br>
    - entity Group<br>
    - repository User<br>

