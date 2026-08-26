# Cookies / Embedded Video

Architecture recommandée:

1. Aucun lecteur tiers nécessitant consentement n'est chargé avant le choix requis.
2. Afficher une miniature locale/proxy autorisée ou un placeholder.
3. Au clic:
   - si consentement valide -> charger l'embed;
   - sinon -> afficher le choix contextuel.
4. Boutons: Accepter / Refuser / Personnaliser.
5. Le refus ne doit pas empêcher la navigation générale du site.
6. Prévoir un lien permanent "Gérer mes cookies".
7. Enregistrer la preuve du choix avec version de politique, finalités et date.
