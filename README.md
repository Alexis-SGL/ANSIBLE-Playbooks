# Ansible

## Présentation

Ansible est un **outil d'automatisation** open-source permettant de configurer, déployer et administrer des systèmes informatiques de manière simple et reproductible.

Il fonctionne **sans agent** sur les machines gérées, ce qui signifie qu'il n'y a rien à installer sur les serveurs cibles. Ansible communique uniquement via **SSH**, ce qui le rend léger et facile à mettre en place.

### Concepts principaux

| Terme | Description |
|---|---|
| **Control node** | Machine sur laquelle Ansible est installé et depuis laquelle les playbooks sont lancés |
| **Machine gérée** | Machine distante sur laquelle Ansible exécute des actions |
| **Inventaire** | Fichier listant les machines à gérer |
| **Playbook** | Fichier YAML décrivant les tâches à automatiser |
| **Module** | Unité d'action Ansible (ex : `apt`, `copy`, `service`) |

---

## Installation d'Ansible

Ansible s'installe uniquement sur la machine de contrôle. Les machines cibles n'ont besoin que d'un accès SSH et de Python 3 (présent par défaut sur Ubuntu).

```bash
sudo apt update && sudo apt upgrade -y
sudo apt install ansible -y
```

### Vérification

```bash
ansible --version
```

---

## Inventaire

L'inventaire est le fichier qui indique à Ansible quelles machines il doit gérer. Les machines sont organisées en **groupes**, ce qui permet de cibler un ensemble de serveurs d'un coup dans un playbook.

```ini
[serveurs]
mon-serveur ansible_host=IP_SERVEUR ansible_user=root
```

### Test de connexion

Une fois l'inventaire créé, on peut tester que toutes les machines sont joignables :

```bash
ansible -i inventory.ini serveurs -m ping
```

Résultat attendu :
```
pong
```

---

## Structure d'un playbook

Un playbook est un fichier YAML qui décrit les actions à réaliser sur les machines cibles. Il est composé d'une ou plusieurs **tâches**, chacune faisant appel à un module Ansible.

```yaml
- name: Description du playbook
  hosts: serveurs       # groupe de machines cibles défini dans l'inventaire
  become: true          # exécution avec les droits sudo

  tasks:
    - name: Description de la tâche
      module_ansible:
        parametre: valeur
```

Chaque tâche est **idempotente** : Ansible vérifie l'état actuel de la machine avant d'agir. Si la tâche est déjà dans l'état souhaité, elle ne fait rien.

---

## Structure type d'un projet

Un projet Ansible est généralement organisé ainsi :

```
.
├── inventory.ini       # liste des machines
├── vars.yml            # variables du projet
├── mon_playbook.yml    # playbook principal
└── files/              # fichiers à copier sur les machines cibles
```

Chaque playbook d'un dépôt est autonome et documenté dans son propre dossier.

---

## Lancer un playbook

```bash
ansible-playbook -i inventory.ini mon_playbook.yml
```

Si le playbook nécessite les droits `sudo` et que la connexion n'est pas en `root` :

```bash
ansible-playbook -i inventory.ini mon_playbook.yml --ask-become-pass
```

Pour cibler un seul serveur parmi un groupe :

```bash
ansible-playbook -i inventory.ini mon_playbook.yml --limit mon-serveur
```
