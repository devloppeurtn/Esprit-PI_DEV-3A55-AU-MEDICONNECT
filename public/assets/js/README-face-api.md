# Script face-api.js (reconnaissance faciale)

Pour éviter le blocage "Tracking Prevention" (Edge/Chrome), le script est chargé depuis ce dossier au lieu du CDN.

**À faire une seule fois :** téléchargez le fichier et enregistrez-le ici sous le nom `face-api.min.js` :

- **URL :** https://cdn.jsdelivr.net/gh/justadudewhohacks/face-api.js@0.22.2/dist/face-api.min.js  
- **Enregistrer sous :** `public/assets/js/face-api.min.js`

Ou en PowerShell (à la racine du projet) :

```powershell
Invoke-WebRequest -Uri "https://cdn.jsdelivr.net/gh/justadudewhohacks/face-api.js@0.22.2/dist/face-api.min.js" -OutFile "public\assets\js\face-api.min.js" -UseBasicParsing
```
