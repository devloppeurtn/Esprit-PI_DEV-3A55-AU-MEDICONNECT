# Récapitulatif des Images - Projet Symphonie

## Images utilisées dans la page Home

Toutes les images suivantes ont été copiées depuis le projet `Clinic-1.0.0` vers `public/assets/img/` dans le projet Symphonie.

### Images principales

1. **Favicon et icônes**
   - `public/assets/img/favicon.png` - Favicon du site
   - `public/assets/img/apple-touch-icon.png` - Icône Apple Touch

2. **Section Hero**
   - `public/assets/img/health/staff-10.webp` - Image principale de la section hero (Modern Healthcare Facility)

3. **Section About**
   - `public/assets/img/health/facilities-9.webp` - Image de la section "About" (Modern medical facility)

4. **Section Featured Departments**
   - `public/assets/img/health/cardiology-1.webp` - Image pour Cardiovascular Medicine
   - `public/assets/img/health/neurology-4.webp` - Image pour Neurological Sciences

5. **Section Featured Services**
   - `public/assets/img/health/consultation-4.webp` - Image principale des services (Premier Healthcare Services)
   - `public/assets/img/health/maternal-2.webp` - Image pour Maternal Care
   - `public/assets/img/health/vaccination-3.webp` - Image pour Vaccination
   - `public/assets/img/health/emergency-1.webp` - Image pour Emergency Care
   - `public/assets/img/health/facilities-6.webp` - Image pour Advanced Technology

6. **Section Find A Doctor (Profils des médecins)**
   - `public/assets/img/health/staff-2.webp` - Photo du Dr. Amanda Foster (Cardiology Specialist)
   - `public/assets/img/health/staff-6.webp` - Photo du Dr. Marcus Johnson (Neurology Expert)
   - `public/assets/img/health/staff-4.webp` - Photo du Dr. Rachel Williams (Pediatrics Care)
   - `public/assets/img/health/staff-8.webp` - Photo du Dr. David Chen (Orthopedic Surgery)
   - `public/assets/img/health/staff-11.webp` - Photo du Dr. Victoria Torres (Dermatology Care)
   - `public/assets/img/health/staff-14.webp` - Photo du Dr. Benjamin Lee (Oncology Treatment)

7. **Section Call To Action**
   - `public/assets/img/health/facilities-9.webp` - Image réutilisée pour Medical Excellence

## Structure des dossiers créés

```
public/
├── assets/
│   ├── img/
│   │   ├── health/          (toutes les images médicales)
│   │   ├── favicon.png
│   │   └── apple-touch-icon.png
│   ├── css/
│   │   └── main.css
│   ├── js/
│   │   └── main.js
│   └── vendor/              (tous les fichiers vendor)
│       ├── bootstrap/
│       ├── bootstrap-icons/
│       ├── aos/
│       ├── glightbox/
│       ├── fontawesome-free/
│       ├── swiper/
│       ├── php-email-form/
│       └── purecounter/
```

## Notes importantes

- Toutes les images sont au format WebP pour une meilleure optimisation
- Les chemins dans le template Twig utilisent la fonction `asset()` de Symfony pour une gestion correcte des assets
- Si vous souhaitez remplacer certaines images, vous pouvez les modifier directement dans le dossier `public/assets/img/health/`
- Le logo peut être ajouté en décommentant la ligne dans le template et en ajoutant `public/assets/img/logo.webp`

## Vérification

Pour vérifier que toutes les images sont présentes, exécutez :

```powershell
Get-ChildItem -Path "public\assets\img\health" -Recurse | Select-Object Name
```
