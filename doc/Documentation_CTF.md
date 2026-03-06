# Documentation Technique : Lab CTF pour Formation Analyste SOC

## 1. Contexte du Projet

Ce laboratoire a été conçu pour simuler un environnement d'entreprise vulnérable. L'objectif est de permettre à des analystes SOC en formation de comprendre les vecteurs d'attaque courants et d'apprendre à détecter les traces d'exploitation dans les outils de monitoring (Wazuh, Splunk, Suricata).

## 2. Objectifs du Challenge

Le CTF (Capture The Flag) propose **7 étapes** à difficulté croissante. L'étudiant doit progresser de l'accès public (Web) vers le contrôle total de la machine (Root).

## 3. Préparation de la VM

- **OS :** Debian 12 (Bookworm)
- **Services :** Apache2, MariaDB, PHP, SSH.
- **Packages utiles :**

```bash
sudo apt update && sudo apt upgrade -y
sudo apt install apache2 mariadb-server php libapache2-mod-php gcc python3 net-tools curl wget auditd ssh -y
```

---

## Flag 1 : Inspection de code (Obfuscation JS)

**Objectif :** Analyser le code source d'une page web pour découvrir une information cachée.

**Procédure pour la création du flag :**

```html
<!-- Fichier : /var/www/html/index.php -->
<!DOCTYPE html>
<html>
  <head>
    <link rel="stylesheet" href="style.css" />
    <link
      rel="icon"
      href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>👨‍💻</text></svg>"
    />
    <title>SEC-LAB CTF</title>
  </head>
  <body>
    <div class="box">
      <h1>Welcome to SEC-LAB</h1>
      <p>Your target is here. Can you find the leaks?</p>
    </div>
    <div id="flagModal" class="modal">
      <div class="modal-content">
        <div class="modal-header">
          <span
            class="close-btn"
            onclick="document.getElementById('flagModal').style.display='none'"
            >&times; [CLOSE]</span
          >
          SYSTEM_OVERRIDE_SUCCESSFUL
        </div>
        <p id="flagText"></p>
      </div>
    </div>

    <script>
      console.log(
        "System status: OK. Debug mode: %cDISABLED",
        "color: red; font-weight: bold;",
      );
      let buffer = [];
      const secretCode = [67, 84, 70, 82, 79, 67, 75, 83, 84, 65, 82];

      document.addEventListener("keydown", (e) => {
        buffer.push(e.keyCode);
        buffer = buffer.slice(-secretCode.length);

        if (JSON.stringify(buffer) === JSON.stringify(secretCode)) {
          showSecretModal();
        }
      });

      function showSecretModal() {
        const _0x5a21 = [
          "\x46\x4c\x41\x47\x5f\x31\x7b\x4a\x53\x5f\x30\x42\x46\x55\x53\x43\x34\x54\x31\x30\x4e\x5f\x57\x31\x5a\x34\x52\x44\x7d",
        ];

        const modal = document.getElementById("flagModal");
        const text = document.getElementById("flagText");

        text.innerHTML = "[*] Accessing encrypted memory area...<br>";
        setTimeout(() => {
          text.innerHTML += "[*] Decrypting sequence...<br>";
          setTimeout(() => {
            text.innerHTML += "[+] SUCCESS: " + _0x5a21[0];
            setTimeout(() => {
              console.log(
                "System status: %cGRANTED",
                "color: #39ff14; font-weight: bold;",
              );
            }, 500);
          }, 500);
        }, 500);

        modal.style.display = "block";
      }
    </script>
  </body>
</html>
```

**Méthode de résolution :**
L'utilisateur doit inspecter le code source ou utiliser la console développeur. La chaîne hexadécimale doit être convertie en ASCII via un script ou un décodeur en ligne.

> **Flag 1 :** `FLAG_1{JS_0BFUSC4T10N_W1Z4RD}`

---

## Flag 2 : Injection SQL (Bypass Login)

**Objectif :** Exploiter une vulnérabilité SQLi pour extraire des informations d'une base de données.

**Procédure pour la création du flag :**

```sql
-- Initialisation de la base de données MariaDB
sudo mariadb -u root
CREATE DATABASE ctf_db;
CREATE USER 'ctf_user'@'localhost' IDENTIFIED BY 'password123';
GRANT ALL PRIVILEGES ON ctf_db.* TO 'ctf_user'@'localhost';
FLUSH PRIVILEGES;
USE ctf_db;
CREATE TABLE flags_table (id INT, flag VARCHAR(100));
-- Insertion du flag cible
INSERT INTO flags_table VALUES (1, 'FLAG_2{SQL_INJECTION_MASTER}');
```

**Méthode de résolution :**
Utilisation d'une injection de type `' OR 1=1 --` sur le formulaire d'authentification ou exploitation via `UNION SELECT` pour lire le contenu de la table `flags_table`.

> **Le Flag 2 :** `FLAG_2{SQL_INJECTION_MASTER}`

---

## Flag 3 : Binaire SUID (Analyse de droits)

**Objectif :** Identifier un binaire avec des droits root mal configurés.

**Procédure pour la création du flag :**
Création du dossier `mkdir -p /opt/ctf_services` et d'un binaire SUID vulnérable.

```c
// Fichier : /opt/ctf_services/flag_reader.c
# Code source simple affichant le flag
#include <stdio.h>
#include <stdlib.h>
#include <unistd.h>

int main() {
    // On force l'UID à 0 (root) pour que le binaire s'exécute vraiment avec ses privilèges
    setuid(0);
    printf("\n[+] System Check Tool v1.0\n");
    printf("[*] Integrity Status: OPTIMAL\n");
    printf("[*] Secret Key Found: FLAG_3{SUID_EXPLOITER_PRO}\n\n");
    return 0;
}
```

Compilation et mise en place du bit SUID

```bash
sudo gcc /root/flag_reader.c -o /usr/local/bin/flag_reader
sudo chown root:root /usr/local/bin/flag_reader
sudo chmod 4755 /usr/local/bin/flag_reader
```

**Méthode de résolution :**
Recherche des fichiers SUID via la commande `find / -perm -4000 2>/dev/null`. L'exécution du binaire `/usr/local/bin/flag_reader` affiche le flag.

> **Flag 3 :** `FLAG_3{SUID_EXPLOITER_PRO}`

---

## Flag 4 : Service Réseau (Interrogation de Port)

**Objectif :** Découvrir un service caché et interagir avec lui.

**Procédure pour la création du flag :**

```python
# Fichier : /opt/ctf_services/service_auth.py
# Script Python simulant un service vulnérable sur le port 1337
import socket

FLAG = "FLAG_4{BUFF_0V3RFL0W_N3TW0RK_88}"
PORT = 1337

def start_service():
    server = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
    server.bind(('0.0.0.0', PORT))
    server.listen(5)
    print(f"[*] Service de licence actif sur le port {PORT}")

    while True:
        client, addr = server.accept()
        client.send(b"--- SEC-LAB LICENSE VERIFIER v1.0 ---\nEnter License Key: ")

        try:
            data = client.recv(1024).decode().strip()

            if len(data) > 64:
                client.send(b"\n[!] BUFFER OVERFLOW DETECTED\n")
                client.send(f"[+] MEMORY DUMP: {FLAG}\n".encode())
            else:
                client.send(b"\n[-] Invalid License Key. Access Denied.\n")
        except:
            pass
        finally:
            client.close()

if __name__ == "__main__":
    start_service()
```

Lancement du service en tâche de fond

```bash
sudo nohup python3 /opt/ctf_services/service_auth.py > service.log 2>&1 &
```

**Méthode de résolution :**
Scan de ports avec `nmap -sV -Pn -p- [IP]`. Test d'attaque depuis une autre machine _(kali)_, puis on envoie une longue chaîne de caractères pour déclencher la condition d'affichage du flag `python3 -c "print('A'*70)" | nc <IP_CTF> 1337`.

> **Flag 4 :** `FLAG_4{BUFF_0V3RFL0W_N3TW0RK}`

---

## Flag 5 : RCE (Upload de fichier)

**Objectif :** Obtenir une exécution de commande système via un upload non sécurisé.

**Procédure pour la création du flag :**

```bash
# Création d'un dossier d'upload vulnérable pour le serveur web
sudo mkdir /var/www/html/uploads
sudo chown www-data:www-data /var/www/html/uploads
# Création du flag protégé à la racine
echo "FLAG_5{RCE_BY_UPLOAD_33}" | sudo tee /flag5.txt
```

```
# Création du fichier : /var/www/html/upload.php
<?php
if(isset($_FILES['file'])){
    $target = "uploads/" . basename($_FILES['file']['name']);
    if(move_uploaded_file($_FILES['file']['tmp_name'], $target)) {
        echo "File uploaded to: " . $target;
    } else {
        echo "Upload failed.";
    }
}
?>
<html>
<head><link rel="stylesheet" href="style.css"></head>
<body>
    <div class="box">
        <h2>Member Profile Picture Update</h2>
        <form enctype="multipart/form-data" method="POST">
            <input type="file" name="file">
            <button type="submit">Upload</button>
        </form>
    </div>
</body>
</html>
```

**Méthode de résolution :**
Upload d'un web shell ou reverse shell PHP (ex: `<?php system($_GET['cmd']); ?>`). Utilisation du shell pour naviguer et lire le fichier `/flag5.txt`.

> **Le Flag 5 :** `FLAG_5{RCE_BY_UPLOAD_33}`

---

## Flag 6 : PrivEsc (Cron Job)

**Objectif :** Escalader ses privilèges vers root via une tâche planifiée.

**Procédure pour la création du flag :**

```bash
# Création d'un script de maintenance modifiable par tous
sudo touch /usr/local/bin/cleanup.sh
sudo chown root:root /usr/local/bin/cleanup.sh
sudo chmod 777 /usr/local/bin/cleanup.sh
# On met un contenu de base
echo "#!/bin/bash" | sudo tee /usr/local/bin/cleanup.sh
echo "echo 'Nettoyage en cours...' > /tmp/clean.log" | sudo tee -a /usr/local/bin/cleanup.sh
# Création du fichier flag dans /root
echo "FLAG_6{CRON_J0B_W1LDC4RD_44}" | sudo tee /root/flag6.txt
# Ajout à la crontab de root
echo "* * * * * root /usr/local/bin/cleanup.sh" | sudo tee -a /etc/crontab
```

**Méthode de résolution :**
L'attaquant identifie le script dans la crontab ou via `find / -writable`. Il modifie le script pour s'octroyer des droits (ex: `echo "chmod +s /bin/bash" >> /usr/local/bin/cleanup.sh`). Dès la nouvelle cron exécutée, exécution de la commande `/bin/bash -p`. Une fois root, il lit `/root/flag6.txt`.

> **Flag 6 :** `FLAG_6{CRON_J0B_W1LDC4RD_44}`

---

## Flag 7 : SSH Crack (Le Boss Final)

**Objectif :** Briser la protection d'une clé privée SSH.

**Procédure pour la création du flag :**

```bash
# Génération d'une clé avec passphrase "orange" et le flag en commentaire
sudo ssh-keygen -t rsa -b 2048 -f /root/id_rsa_final -N "orange" -C "FLAG_7{SSH_CRACK_P4SSPHR4S3_77}"
```

**Méthode de résolution :**
Récupération du fichier `id_rsa_final`. Utilisation de `ssh2john` sur Kali Linux pour obtenir le hash, puis craquage avec `john --wordlist=rockyou.txt`. Lecture du commentaire de la clé via `ssh-keygen -y -f id_rsa_final`.

> **Flag 7 :** `FLAG_7{SSH_CRACK_P4SSPHR4S3_77}`
