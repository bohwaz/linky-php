# Linky.php

```
% linky monthly -m 3
02/2023 181 kWh ▄▄▄▄▄▄▄▄▄▄▄▄▄▄▄▄▄▄▄▄▄▄▄
03/2023 304 kWh ▄▄▄▄▄▄▄▄▄▄▄▄▄▄▄▄▄▄▄▄▄▄▄▄▄▄▄▄▄▄▄▄▄▄▄▄▄▄▄▄
04/2023 211 kWh ▄▄▄▄▄▄▄▄▄▄▄▄▄▄▄▄▄▄▄▄▄▄▄▄▄▄▄
```

Ceci est un outil simple, sans aucune autre dépendance que PHP pour consulter sa consommation électrique EDF.

* Affichage en ligne de commande
* Export JSON
* Affichage par jour, semaine ou mois

## Installation

```
sudo apt install php-cli
mkdir ~/.local/bin
wget -O ~/.local/bin/linky https://raw.githubusercontent.com/bohwaz/linky-php/main/linky.php
chmod +x ~/.local/bin/linky
```

Si `~/.local/bin` n'est pas dans votre `$PATH` vous pouvez l'ajouter :

```
echo >> ~/.bashrc
echo 'PATH=~/.local/bin/:$PATH' >> ~/.bashrc
export PATH=~/.local/bin/:$PATH
```

## Première utilisation

1. Ouvrir un compte sur <https://mon-compte.enedis.fr/>
2. Créer un access token sur https://conso.vercel.app/
3. Exécuter la commande donnée par conso.vercel.app

## Utilisation

Exécuter `linky help` pour les détails des commandes et options.
