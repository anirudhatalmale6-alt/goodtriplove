# Security Center Checklist

## Admin
- [ ] 2FA obligatoire pour Super Admin
- [ ] 2FA obligatoire pour Admin
- [ ] rôles distincts: super_admin, admin, moderator, professional, user
- [ ] aucun Admin standard ne peut supprimer les logs d'audit
- [ ] alertes nouvelle connexion activées
- [ ] appareils/session révocables

## Monitoring
- [ ] DB check
- [ ] Mail check
- [ ] Ollama check
- [ ] YouTube API check
- [ ] Cron heartbeat à ajouter
- [ ] Queue heartbeat à ajouter
- [ ] Disk usage check à ajouter selon hébergement
- [ ] HTTP home page check à ajouter si environnement le permet

## Backups
- [ ] backup MariaDB quotidien
- [ ] fichiers chmod 600
- [ ] rétention définie
- [ ] restauration testée
- [ ] backup hors serveur recommandé en plus

## Videos
- [ ] tâche périodique pour détecter vidéo supprimée/privée
- [ ] masquer automatiquement les embeds morts
- [ ] file Admin pour vérification manuelle

## Privacy
- [ ] durée de rétention des IP/logs définie
- [ ] export des données utilisateur
- [ ] suppression de compte
- [ ] politique de confidentialité
- [ ] consentement cookies si nécessaire
