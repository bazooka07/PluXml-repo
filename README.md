# Générateur de dépôt statique pour les extensions de PluXml (C.M.S.)

Utilise les pages statiques (Github pages) pour afficher des extensions pour PluXml.

Les extensions sont à stocker dans la branche "gh-pages" et dans les dossiers plugins, themes ou scripts.

Toutes les extensions (plugins, thèmes, scripts) doivent être des archives zip contenant un dossier racine unique qui doit contenir un fichier infos.xml.

## Installation


- Connectez-vous sur votre compte à https://github.com
- Forkez ce dépôt en allant à la page https://github.com/bazooka07/PluXml-repo et en cliquant sur le bouton fork
- Sur la nouvelle page, cliquez sur le bouton "Clone or download" pour recopier l'adresse de votre nouveau dépôt dans le presse-papier
- Sur votre ordinateur, clonez votre nouveau dépôt :

`git clone https://github.com/votre-compte/PluXml-repo.git `

- Allez dans le dossier PluXml-repo
- Basculez vers une nouvelle branche nommée impérativment *gh-pages* :

`git checkout -b gh-pages`

- recopiez les archives au format zip de vos plugins, thèmes ou scripts dans les dossiers plugins, themes ou scripts respectivement
- tapez la commande :

`./build.php`

pour créer les catalogues au format JSON des différentes extensions
- Vous devez maintenant enregistrer les différentes modifications (*commit*) et les pousser sur votre compte Github (*push*) :

```
git add .
git commit -am 'le message qui vous convient' 
git push origin gh-pages
```

- A l'aide de votre navigateur, allez à l'adresse : 

https://votre-compte.github.io/PluXml-repo/ 

pour visiter votre nouveau dépôt d'extensions pour PluXml.
- Pour ajouter de nouvelles extensions à votre dépôt, copiez les dans leurs dossiers et répétez les commandes *commit* et *push* de git. 
