# 🌤️ MÉTÉO - QUICK START GUIDE

## En 5 minutes ⚡

### 1️⃣ Obtenir une clé API (2 min)
```
1. Aller sur https://openweathermap.org/
2. Sign Up → Créer compte avec email
3. Confirmer l'email
4. Dashboard → API keys → Copier la clé
```

### 2️⃣ Ajouter à .env (1 min)
```env
# .env ou .env.local
WEATHER_API_KEY=votre_clé_copiée_ici
```

### 3️⃣ Tester (2 min)
```
1. Créer un événement:
   - Titre: "Test Météo"
   - Lieu: "Paris, France"  ← Important!
   - Date: Aujourd'hui
   
2. Voir détail: /evenement/{id}
3. 🌤️ Voir la météo!
```

---

## 📊 Qu'est-ce qui s'affiche?

```
┌────────────────────────────────────┐
│ 🌤️ Météo du jour de l'événement   │
│                                    │
│ ☁️          Nuageux    65%         │
│ 15°C        Paris (FR) 4.5 m/s     │
│ Ress: 14°C             1013 hPa    │
└────────────────────────────────────┘
```

---

## 🔑 Clé API

- **Gratuit**: 1,000 appels/jour
- **Quota**: 5 appels/minute
- **Coût**: 0€
- **Durée**: Illimitée

---

## ❓ Questions fréquentes

**Q: Ça marche sans clé?**  
A: Oui, mais pas de météo (pas d'erreur)

**Q: Ça ralentit le site?**  
A: Non, ~200ms par événement

**Q: C'est sécurisé?**  
A: Oui, clé dans .env (jamais dans le code)

**Q: Support France?**  
A: Oui, tous les pays

**Q: Comment voir les logs?**  
A: `tail -f var/log/dev.log | grep -i weather`

---

## 📚 Documentation complète

- [WEATHER_SETUP.md](WEATHER_SETUP.md) - Configuration détaillée
- [WEATHER_FEATURE.md](WEATHER_FEATURE.md) - Architecture technique
- [FILE_INDEX.md](FILE_INDEX.md) - Index complet

---

## ✅ Checklist simple

- [ ] Account OpenWeatherMap créé
- [ ] Clé API copiée
- [ ] `WEATHER_API_KEY` ajoutée à `.env`
- [ ] Événement testé avec lieu réel
- [ ] Météo visible sur `/evenement/{id}`

**Fait!** 🎉

---

*C'est tout ce qu'il y a à faire. Disfruta!* 🌤️
