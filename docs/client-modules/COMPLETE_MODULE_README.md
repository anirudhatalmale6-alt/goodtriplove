# GoodTripLove — Complete Security Module

Ce ZIP regroupe en un seul module les deux ensembles de sécurité GoodTripLove:

## A. Security & Anti-Spam
- Cloudflare Turnstile
- Rate limiting IP / compte
- Anti brute-force
- Blocage temporaire IP / compte
- Anti-inscription automatisée
- Détection des doublons
- Détection d'URL suspectes
- Protection SQL injection / XSS / CSRF via bonnes pratiques Laravel
- Hash sécurisé des mots de passe
- Sessions sécurisées
- Vérification email par code à 6 chiffres
- Sécurité reset password
- Logs de connexion
- Logs de sécurité
- Alertes Admin
- Protection routes Admin
- Rôles et permissions
- Validation des uploads

## B. Security Center & Monitoring
- 2FA obligatoire Admin / Super Admin
- Gestion des appareils et sessions
- Détection nouvelle connexion
- Audit avec ancienne / nouvelle valeur
- Logs d'audit protégés
- Security Center
- Health checks DB / Mail / YouTube API / Ollama
- Alertes sécurité
- Sauvegarde applicative MariaDB
- Contrôle des services
- Modération / signalements
- Préparation sécurité API
- Vérification périodique des vidéos supprimées/privées
- Checklist RGPD / conservation des logs

## Important
Le freelance doit adapter ce module au code réel GoodTripLove:
- modèle User existant
- système de rôles existant
- routes Auth existantes
- layout Admin
- scheduler / queues
- SMTP
- DirectAdmin
- MariaDB

Ne pas remplacer aveuglément des fichiers existants.

## Ordre recommandé
1. Intégrer les migrations
2. Configurer .env
3. Brancher les middleware
4. Brancher Turnstile
5. Activer logs sécurité
6. Activer 2FA Admin
7. Activer Security Center
8. Configurer alertes mail
9. Configurer scheduler / cron
10. Tester chaque protection en staging avant production
