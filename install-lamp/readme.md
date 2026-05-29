> #### Playbook Ansible — install_LAMP

---

# Déploiement automatisé d'un serveur LAMP

## Objectif

L'objectif de ce playbook est de déployer un **serveur LAMP** (Linux, Apache, MySQL/MariaDB, PHP) de manière automatisée à l'aide d'Ansible sur une machine Ubuntu Server.

Le playbook installe et configure les services nécessaires, déploie un VirtualHost Apache personnalisé via template Jinja2, installe phpMyAdmin, crée un utilisateur base de données, et déploie une page d'accueil de test.

Ce playbook est conçu pour être **générique et réutilisable** : il suffit d'adapter les variables et l'inventaire pour cibler n'importe quel serveur et domaine.

---

## Prérequis

Avant de lancer le playbook, les éléments suivants doivent être en place.

### Machine de contrôle Ansible
- Système Linux
- Ansible installé (`ansible-galaxy collection install community.mysql`)
- Accès réseau vers la machine serveur
- Clé SSH configurée

### Machine serveur (cible)
- Ubuntu Server
- Accès SSH actif
- Python 3 installé

---

## Connexion SSH au serveur distant

Ansible communique avec les machines distantes via **SSH**.
Afin de simplifier l'administration et d'éviter l'utilisation de mots de passe,
une authentification **par clé SSH** a été mise en place pour le compte `root`.


### Génération de la clé SSH sur la machine de contrôle

Sur la machine de contrôle Ansible, une clé SSH dédiée est générée :

```bash
ssh-keygen -t ed25519 -f ~/.ssh/key_ansible
```

Cette commande génère :
- une clé privée : `~/.ssh/key_ansible`
- une clé publique : `~/.ssh/key_ansible.pub`

### Déploiement de la clé SSH sur le serveur cible



Copier la clé publique retournée par :
```bash
cat ~/.ssh/key_ansible.pub
```

La clé publique est ensuite ajoutée manuellement au compte `root` :
```bash
ssh NOM_UTILISATEUR@IP_DU_SERVEUR
sudo -i
mkdir -p /root/.ssh
chmod 700 /root/.ssh
nano /root/.ssh/authorized_keys
chmod 600 /root/.ssh/authorized_keys
```

Le fichier `authorized_keys` contient la clé publique `key_ansible`.

### Configuration SSH pour le compte root

Le service SSH est configuré afin d'autoriser le compte `root` uniquement par clé SSH :
```bash
sudo nano /etc/ssh/sshd_config
```
```
PermitRootLogin prohibit-password
PubkeyAuthentication yes
```

Cette configuration empêche toute connexion `root` par mot de passe.

```bash
systemctl reload ssh
```

### Test de la connexion SSH root par clé

Depuis la machine de contrôle Ansible :
```bash
ssh -i ~/.ssh/key_ansible root@IP_DU_SERVEUR
```

La connexion doit s'effectuer sans demande de mot de passe.

### Test de la connexion Ansible

```bash
ansible -i inventory.ini webservers -m ping
```

Résultat attendu :
```
pong
```

---

## Structure du projet

```
.
├── inventory.ini
├── vars.yml
├── install_LAMP.yml
├── files/
│   └── index.php
└── templates/
    └── vhost.conf.j2
```

---

## Inventaire des machines

### Fichier inventory.ini

Le fichier d'inventaire définit les machines cibles. Le `fqdn` est renseigné directement au niveau du serveur pour permettre une configuration individuelle par machine.

```ini
[local]
localhost ansible_connection=local

[webservers]
serveur_web_1 ansible_host=IP_SERVEUR ansible_user=root fqdn=www.mon-domaine.fr
```

> Pour ajouter un second serveur, il suffit d'ajouter une ligne avec son propre `fqdn` :
> ```ini
> serveur_web_2 ansible_host=IP_SERVEUR2 ansible_user=root fqdn=blog.mon-domaine.fr
> ```

---

## Variables du projet

Les variables nécessaires à l'installation du serveur LAMP sont définies dans `vars.yml`.

### Fichier vars.yml

```yaml
doc_root: /var/www/{{ fqdn }}
vhost: "vhost.conf.j2"

php_version: "8.4"

web:
  - "php{{ php_version }}"
  - "php{{ php_version }}-cli"
  - "php{{ php_version }}-common"
  - "php{{ php_version }}-mysql"
  - "libapache2-mod-php{{ php_version }}"
  - apache2

#Choisir 'mysql' ou 'mariadb'
database: mysql

mysql:
  - mysql-server

mariadb:
  - mariadb-server

phpmyadmin:
  - phpmyadmin
  - python3-pymysql

db_user: admin
db_password: phpmyadmin
```

| Variable | Description |
|---|---|
| `fqdn` | Nom de domaine complet, défini dans `inventory.ini` par serveur |
| `doc_root` | Chemin racine du site web, construit à partir du `fqdn` |
| `vhost` | Nom du template Jinja2 utilisé pour le VirtualHost Apache |
| `php_version` | Version de PHP à installer (via le dépôt Ondřej) |
| `database` | Moteur de base de données : `mysql` ou `mariadb` |
| `db_user` / `db_password` | Identifiants de l'utilisateur administrateur de la base de données |

---

## Template du VirtualHost Apache

Le VirtualHost est généré dynamiquement par Ansible via un template Jinja2.
Il configure le site en HTTP avec les logs associés.

### Fichier templates/vhost.conf.j2

```apache
<VirtualHost *:80>
    ServerName {{ fqdn }}
    ServerAlias www.{{ fqdn }}

    DocumentRoot {{ doc_root }}

    <Directory {{ doc_root }}>
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/{{ fqdn }}-error.log
    CustomLog ${APACHE_LOG_DIR}/{{ fqdn }}-access.log combined
</VirtualHost>
```

Ce fichier est placé dans `templates/vhost.conf.j2` et déployé via la tâche `template` du playbook.

---

## Page web de test

Une page `index.php` est déployée afin de vérifier le bon fonctionnement du serveur.
Elle affiche des informations dynamiques sur l'environnement : URL du site, système, version d'Apache, version de PHP et base de données active.

Le fichier est placé dans `files/index.php` et copié automatiquement par le playbook.

---

## Playbook Ansible : installation du serveur LAMP

Le playbook automatise :
- la mise à jour du système
- l'installation d'Apache, PHP et du moteur de base de données choisi
- l'activation du module PHP pour Apache
- l'installation et la configuration de phpMyAdmin
- la création d'un utilisateur administrateur base de données
- le déploiement du VirtualHost via template Jinja2
- le déploiement de la page web de test

### Fichier install_LAMP.yml

```yaml
# ansible-playbook -i inventory.ini install_LAMP.yml

- name: Installation complète d'un serveur LAMP
  hosts: webservers
  become: true

  vars_files:
    - vars.yml

  tasks:
    #Ajout du dépôt PHP Ondřej pour accéder à la version choisie de PHP
    - name: Ajouter le dépôt PHP Ondřej
      apt_repository:
        repo: ppa:ondrej/php
        state: present

    - name: Mettre à jour les paquets
      apt:
        update_cache: yes

    - name: Installer les paquets pour le serveur web
      apt:
        name: "{{ web }}"
        state: present

    - name: Installer les paquets pour la base de données mysql
      apt:
        name: "{{ mysql }}"
        state: present
      when: database == 'mysql'

    - name: Installer les paquets pour la base de données mariadb
      apt:
        name: "{{ mariadb }}"
        state: present
      when: database == 'mariadb'

    - name: Désactiver toutes les versions PHP existantes
      shell: a2dismod php*
      ignore_errors: true

    - name: Activer PHP {{ php_version }}
      command: a2enmod php{{ php_version }}

    - name: Recharger Apache après activation PHP
      service:
        name: apache2
        state: reloaded

    - name: S'assurer qu'Apache est démarré
      service:
        name: apache2
        state: started
        enabled: true

    - name: S'assurer que MySQL est démarré
      service:
        name: mysql
        state: started
        enabled: true
      when: database == 'mysql'

    - name: S'assurer que MariaDB est démarrée
      service:
        name: mariadb
        state: started
        enabled: true
      when: database == 'mariadb'

    - name: Préconfigurer phpMyAdmin - serveur web
      debconf:
        name: phpmyadmin
        question: phpmyadmin/reconfigure-webserver
        value: apache2
        vtype: multiselect

    - name: Préconfigurer phpMyAdmin - dbconfig-common
      debconf:
        name: phpmyadmin/dbconfig-install
        question: phpmyadmin/dbconfig-install
        value: true
        vtype: boolean

    - name: Installer phpMyAdmin
      apt:
        name: "{{ phpmyadmin }}"
        state: present

    - name: Créer un utilisateur DB admin avec tous les droits
      community.mysql.mysql_user:
        name: "{{ db_user }}"
        password: "{{ db_password }}"
        host: "%"
        priv: "*.*:ALL,GRANT"
        state: present
        login_unix_socket: /var/run/mysqld/mysqld.sock

    - name: Créer le dossier du site
      file:
        path: "{{ doc_root }}"
        state: directory
        owner: www-data
        group: www-data
        mode: '0755'

    - name: Copier la page index.php
      copy:
        src: files/index.php
        dest: "{{ doc_root }}/index.php"
        owner: www-data
        group: www-data
        mode: '0644'

    - name: Déployer le VirtualHost
      template:
        src: templates/{{ vhost }}
        dest: "/etc/apache2/sites-available/{{ fqdn }}.conf"

    - name: Activer le site {{ fqdn }}
      command: "a2ensite {{ fqdn }}.conf"

    - name: Désactiver le site par défaut
      command: a2dissite 000-default.conf

    - name: Vérifier la configuration Apache
      command: apache2ctl configtest

    - name: Reload Apache
      service:
        name: apache2
        state: reloaded
```

---

## Lancement du playbook

```bash
ansible-playbook -i inventory.ini install_LAMP.yml
```

Le site est ensuite accessible depuis un navigateur à l'adresse définie par le `fqdn` renseigné dans l'inventaire :

```
http://www.mon-domaine.fr
```

---

## Conclusion

Ce playbook permet de déployer un serveur LAMP complet de manière reproductible et cohérente sur n'importe quel serveur Ubuntu. La configuration est entièrement portée par deux fichiers (`inventory.ini` et `vars.yml`), ce qui rend le playbook réutilisable sans aucune modification du code. Il suffit de renseigner l'IP du serveur, le `fqdn` souhaité et les identifiants base de données pour cibler un nouvel environnement.
