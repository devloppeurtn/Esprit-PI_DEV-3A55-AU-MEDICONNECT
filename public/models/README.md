# Modèles face-api.js (reconnaissance faciale)

Téléchargez le dépôt **face-api.js-models** et copiez les dossiers nécessaires ici :

1. Clonez ou téléchargez : https://github.com/justadudewhohacks/face-api.js-models  
2. Copiez dans `public/models/` les dossiers :
   - `face_landmark_68/` (landmarks pour le descripteur)
   - `face_recognition/` (embedding 128-d)
   - `ssd_mobilenetv1/` ou `tiny_face_detector/` (détection de visage)

Exemple (depuis la racine du projet) :

```bash
git clone https://github.com/justadudewhohacks/face-api.js-models.git temp-models
cp -r temp-models/face_landmark_68 temp-models/face_recognition temp-models/tiny_face_detector public/models/
```

Sous Windows : copiez manuellement les dossiers `face_landmark_68`, `face_recognition` et `tiny_face_detector` dans `public\models\`.
