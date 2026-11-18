<?php

return [
    'nav' => [
        'skip' => 'Aller au contenu',
        'menu' => 'Menu',
        'main' => 'Navigation principale',
        'footer' => 'Informations',
        'pieces' => 'Créations',
        'atelier' => "L'atelier",
        'archive' => 'Pièces vendues',
        'switch_to' => 'Français',
        'cart' => 'Panier',
        'cart_items' => '{1} :count pièce dans le panier|[2,*] :count pièces dans le panier',
    ],

    'home' => [
        'title' => 'Le feu dans la glace',
        'lede' => "Bijoux uniques en émail grand feu sur argent et cuivre, rehaussés d'or et de cabochons, façonnés à la main dans l'atelier de Chamonix.",
        'cta' => 'Voir les créations',
        'collections' => 'Collections',
        'available' => 'Disponibles en ce moment',
        'see_all' => 'Toutes les créations',
    ],

    'promises' => [
        'label' => 'Nos engagements',
        'unique_title' => 'Chaque pièce est unique',
        'unique_text' => "Chaque bijou est émaillé et cuit à la main. Il n'en existe qu'un seul exemplaire.",
        'hallmark_title' => 'Poinçonné',
        'hallmark_text' => 'Les pièces en métal précieux portent le poinçon de maître de l’atelier.',
        'shipping_title' => 'Envoi assuré',
        'shipping_text' => 'Expédition suivie, assurée et remise contre signature, dans un écrin.',
    ],

    'status' => [
        'sold' => 'Vendu',
        'reserved' => 'Paiement en cours',
    ],

    'piece' => [
        'photos' => 'Photos',
        'show_photo' => 'Afficher la photo :n',
        'materials' => 'Matières',
        'dimensions' => 'Dimensions',
        'edition' => 'Édition',
        'unique' => 'Pièce unique',
        'add_to_cart' => 'Ajouter au panier',
        'in_cart' => 'Dans votre panier',
        'shipping_note' => 'Expédition assurée sous 2 à 5 jours ouvrés. Retour possible sous 14 jours.',
        'reserved_note' => "Quelqu'un est en train de régler cette pièce. Si le paiement n'aboutit pas, elle sera de nouveau disponible d'ici une demi-heure.",
        'sold_note' => 'Cette pièce a trouvé sa place. Kiki peut créer une pièce dans le même esprit.',
        'ask_commission' => 'Demander une création',
        'related' => 'Dans la même collection',
    ],

    'inquiry' => [
        'title' => 'Une question sur cette pièce ?',
        'intro' => 'Taille, port, création sur mesure : écrivez à Kiki, elle vous répond personnellement.',
        'name' => 'Votre nom',
        'email' => 'Votre e-mail',
        'message' => 'Votre message',
        'send' => 'Envoyer le message',
        'sent' => 'Message envoyé. Kiki vous répond rapidement par e-mail.',
    ],

    'pieces' => [
        'title' => 'Créations',
        'intro' => 'Toutes les pièces actuellement disponibles. Chacune existe en un seul exemplaire.',
        'empty' => 'Aucune pièce n’est disponible en ce moment : une nouvelle série est en cours de cuisson.',
        'see_archive' => 'Voir les pièces déjà vendues',
    ],

    'archive' => [
        'title' => 'Pièces vendues',
        'intro' => "Les pièces qui ont déjà trouvé preneur. Pour une création dans le même esprit, écrivez à l'atelier.",
        'empty' => 'Aucune pièce vendue pour le moment.',
    ],

    'collection' => [
        'empty' => 'Toutes les pièces de cette collection ont été vendues.',
        'sold_count' => '{1} Voir la pièce déjà vendue|[2,*] Voir les :count pièces déjà vendues',
    ],

    'cart' => [
        'title' => 'Panier',
        'empty' => 'Votre panier est vide.',
        'added' => '« :title » a été ajouté au panier.',
        'not_available' => "La pièce :ref n'est plus disponible.",
        'no_longer_available' => "Cette pièce n'est plus disponible. Retirez-la pour continuer.",
        'remove' => 'Retirer',
        'destination' => 'Destination de la livraison',
        'update_destination' => 'Mettre à jour',
        'subtotal' => 'Bijoux',
        'shipping' => 'Livraison',
        'choose_destination' => 'Choisissez une destination',
        'total' => 'Total',
        'checkout' => 'Payer',
        'checkout_note' => 'Paiement sécurisé par Stripe. Les pièces vous sont réservées pendant :minutes minutes.',
    ],

    'checkout' => [
        'terms_notice' => 'En payant, vous acceptez nos [conditions générales de vente](:url).',
        'unavailable' => "Désolé, ces pièces viennent d'être réservées ou vendues : :refs. Retirez-les du panier pour continuer.",
        'failed' => "Le paiement n'a pas pu démarrer. Réessayez dans un instant ; si le problème persiste, écrivez-nous.",
        'cancelled' => 'Paiement annulé. Vos pièces sont toujours dans le panier.',
        'thanks_title' => 'Merci pour votre commande',
        'thanks_order' => 'Votre commande :number est confirmée. Un récapitulatif a été envoyé à :email.',
        'thanks_pending' => 'Votre paiement a bien été reçu. Vous allez recevoir la confirmation par e-mail dans quelques minutes.',
        'thanks_next' => 'Kiki prépare votre bijou et vous enverra le numéro de suivi dès l’expédition.',
        'back_home' => "Retour à l'accueil",
    ],

    'mail' => [
        'confirmation_subject' => 'Votre commande :number',
        'confirmation_title' => 'Merci pour votre commande',
        'confirmation_intro' => 'Bonjour :name, votre paiement a bien été reçu. Voici le récapitulatif de votre commande.',
        'piece' => 'Pièce',
        'price' => 'Prix',
        'order_number' => 'Numéro de commande',
        'ship_to' => 'Livraison à',
        'confirmation_next' => 'Votre bijou sera emballé avec soin et expédié sous 2 à 5 jours ouvrés. Vous recevrez le numéro de suivi par e-mail. Pour toute question, répondez simplement à ce message.',
        'signature' => 'À bientôt,',
        'shipped_subject' => 'Votre commande :number est en route',
        'shipped_title' => 'Votre bijou est en route',
        'shipped_intro' => 'Bonjour :name, votre commande :number vient de partir de l’atelier.',
        'carrier' => 'Transporteur',
        'tracking_number' => 'Numéro de suivi',
        'track' => 'Suivre le colis',
        'shipped_outro' => 'La remise se fait contre signature. Pour toute question, répondez simplement à ce message.',
    ],

    'errors' => [
        'not_found_title' => 'Page introuvable',
        'not_found_text' => "Cette page n'existe pas ou plus. La pièce que vous cherchez a peut-être été vendue.",
    ],
];
