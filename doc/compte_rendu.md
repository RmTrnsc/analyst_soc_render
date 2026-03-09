# Compte Rendu du Projet : Déploiement d'un SOC/SIEM avec Scénario CTF

[lien github](https://github.com/RmTrnsc/analyst_soc_render/tree/main)

## Introduction

Ce projet a pour objectif de simuler un environnement de Centre des Opérations de Sécurité (SOC) réaliste. L'infrastructure mise en place comprend une stack de sécurité défensive (Blue Team) chargée de surveiller et d'analyser les activités sur une machine cible vulnérable, tandis qu'une équipe offensive (Red Team) tente de compromettre cette machine et de capturer des "flags".

Les principaux objectifs sont :

- Déployer et configurer une stack SIEM complète avec des outils open-source de premier plan : Wazuh, Splunk, et Suricata.
- Automatiser le déploiement de l'infrastructure et la configuration des outils à l'aide de Terraform et Ansible.
- Mettre en place des scénarios d'attaque réalistes pour tester l'efficacité des détections.

---

## 1. Automatisation de l'Infrastructure et de la Configuration

Pour garantir un déploiement rapide, reproductible et cohérent, j'ai utilisé des outils d'Infrastructure as Code (IaC) et de gestion de configuration.

### Terraform pour le Provisioning

Terraform a été utilisé pour le provisioning de l'infrastructure. Le code a été versionné et géré localement.

On peut voir ci-dessous le fichier `main.tf` qui définit les ressources à créer, ainsi que le fichier de variables `vars.tf`.

![Fichier main.tf](assets/terraform/main_tf.png)
![Fichier main.tf 2](assets/terraform/main_tf_2.png)

![Fichier de variables](assets/terraform/vars.png)

L'exécution de `terraform plan` et `terraform apply` a permis de créer l'infrastructure nécessaire. Le compte rendu de l'exécution est visible dans le fichier `terraform_plan_apply.md`.

### Ansible pour la Configuration

Une fois l'infrastructure provisionnée, Ansible a été utilisé pour automatiser la configuration des machines. Les tâches comprenaient l'installation de Docker, la création d'utilisateurs et de groupes, et le déploiement des services.

Ci-dessous, le playbook pour l'installation de Docker et la configuration des utilisateurs.

![Installation d'Ansible](assets/ansible/installation.png)
![Fichier d'inventaire hosts](assets/ansible/hosts.png)
![Playbook d'installation de Docker](assets/ansible/install_docker.png)
![Vérification de l'installation de Docker](assets/ansible/docker.png)
![Gestion des utilisateurs et groupes](assets/ansible/user_groups.png)

---

## 2. Déploiement de la Stack de Sécurité

### Wazuh - SIEM et Détection d'Intrusions

Wazuh a été déployé en tant que SIEM principal pour la collecte de logs et la détection d'intrusions sur les hôtes. Le déploiement a été réalisé à l'aide de Docker Compose, en clônant le dépôt officiel.

![Clone du dépôt Wazuh](assets/wazuh/git_clone.png)
![Fichier Docker Compose de Wazuh](assets/wazuh/docker-compose.png)

Un agent Wazuh a été déployé sur la machine cible CTF pour collecter et remonter les logs système et applicatifs vers le manager.

![Installation de l'agent Wazuh](assets/wazuh/wazuh_agent.png)

Grâce à cette configuration, j'ai pu détecter plusieurs types d'activités malveillantes :

- **Tentative d'injection SQL** : Détectée sur le service web de la machine cible.
- **Détection de binaire malveillant** : Un fichier suspect a été identifié.
- **Suppression de malware** : L'agent a réagi en supprimant le fichier malveillant.

![Détection d'une tentative SQLi](assets/wazuh/sql_attempt.png)
![Détection d'un binaire suspect](assets/wazuh/detect_binary.png)
![Suppression du malware](assets/wazuh/detect_remove_maware.png)

### Splunk - Analyse et Corrélation de Logs

Splunk a été utilisé en complément de Wazuh pour l'analyse avancée et la corrélation de logs. Les alertes de Wazuh ont été forwardées vers Splunk pour une investigation plus poussée et la création de dashboards de supervision.

L'instance Splunk a également été déployée via Docker.

![Installation de Splunk](assets/splunk/installation.png)
![Démarrage de Splunk](assets/splunk/start.png)

J'ai pu visualiser les logs Docker et les alertes remontées, comme la tentative d'injection SQL détectée initialement par Wazuh.

![Logs Docker dans Splunk](assets/splunk/docker_logs.png)
![Alerte d'injection SQL dans Splunk](assets/splunk/sql_attempt.png)

### Suricata - Détection d'Intrusions Réseau (IDS)

Pour surveiller le trafic réseau, j'ai déployé Suricata. Cet outil analyse les paquets en temps réel et détecte les signatures d'attaques connues.

![Installation de Suricata](assets/suricata/installation.png)
![Configuration de Suricata](assets/suricata/configuration.png)

---

## 3. Scénarios d'Attaque et CTF

Pour valider l'efficacité de la stack de sécurité, une machine cible vulnérable (CTF) est configurée sur un OS Debian 12. Elle expose plusieurs services (Apache, MariaDB, SSH) et intègre 7 flags. Chaque flag correspond à une étape d'une attaque simulée, permettant de tester la réactivité du SIEM.

### Les Flags du CTF

1.  **Flag 1 : Inspection de code (Obfuscation JS)**
    - **Description :** Le flag est caché dans le code JavaScript de la page d'accueil via une simple obfuscation.
    - **Intérêt :** Tester la vigilance et la capacité d'analyse de code source côté client.

2.  **Flag 2 : Injection SQL (Bypass Login)**
    - **Description :** Une injection SQL permet de contourner un formulaire de login et d'extraire le flag de la base de données.
    - **Intérêt :** Valider la détection des attaques web par Wazuh et Suricata, générant des alertes pour "SQL Injection".

3.  **Flag 3 : Binaire SUID**
    - **Description :** Un binaire avec le bit SUID, trouvable avec une simple commande `find`, exécute une commande qui révèle le flag.
    - **Intérêt :** Détecter l'exploitation de privilèges anormaux et la post-exploitation.

4.  **Flag 4 : Service Réseau caché**
    - **Description :** Un service Python écoute sur un port non standard. L'envoi d'une chaîne de caractères longue provoque un buffer overflow qui expose le flag.
    - **Intérêt :** Vérifier la détection de scans de ports et d'interactions avec des services non standards.

5.  **Flag 5 : RCE via Upload de fichier**
    - **Description :** Une page PHP permet l'upload de fichiers sans restriction. Le téléversement d'un web shell permet d'exécuter des commandes à distance.
    - **Intérêt :** Tester la détection de téléversement de fichiers malveillants et de Remote Code Execution (RCE).

6.  **Flag 6 : Élévation de Privilèges via Cron Job**
    - **Description :** Un script exécuté par une tâche `cron` root possède des permissions d'écriture pour tous. Le modifier permet d'obtenir un shell root.
    - **Intérêt :** Valider la surveillance de l'intégrité des fichiers (FIM) et la détection de techniques de persistance ou d'escalade.

7.  **Flag 7 : Crack de clé privée SSH**
    - **Description :** Une clé privée SSH est protégée par une passphrase faible. Le flag est contenu dans le commentaire de la clé.
    - **Intérêt :** Simuler la détection d'attaques par force brute ou la compromission de secrets.

---

## Conclusion

Ce projet a permis de construire une chaîne de sécurité complète et fonctionnelle, centrée sur la détection d'intrusions. L'automatisation via Terraform et Ansible s'est avérée être un atout majeur, garantissant un déploiement rapide et une gestion simplifiée de l'environnement.

La validation des outils (Wazuh, Splunk, Suricata) à travers les scénarios du CTF a démontré leur efficacité pour détecter et tracer une large gamme d'attaques simulées. Ce laboratoire constitue ainsi une base solide et une expérience pratique précieuse pour quiconque s'intéresse à la mise en place et à la gestion d'un SOC.
