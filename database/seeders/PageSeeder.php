<?php

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;

/**
 * Creates the standard pages with placeholder text. Safe to run in
 * production: existing pages are never overwritten.
 *
 * The legal texts are outlines to complete and have checked, not final
 * legal documents. Everything in [brackets] must be filled in.
 */
class PageSeeder extends Seeder
{
    public function run(): void
    {
        $pages = [
            [
                'slug' => 'atelier',
                'title_fr' => "L'atelier",
                'title_en' => 'The atelier',
                'show_in_footer' => false,
                'position' => 0,
                'body_fr' => "Kiki Fornell crée chaque bijou à la main dans son atelier de Chamonix-Mont-Blanc.\n\n[Texte à compléter : parcours, technique de l'émail grand feu, inspiration « le feu dans la glace ».]",
                'body_en' => "Kiki Fornell makes every piece by hand in her atelier in Chamonix-Mont-Blanc.\n\n[Text to complete: background, the grand feu enamel technique, the “fire in ice” inspiration.]",
            ],
            [
                'slug' => 'livraison-retours',
                'title_fr' => 'Livraison et retours',
                'title_en' => 'Shipping and returns',
                'position' => 1,
                'body_fr' => "## Livraison\n\nChaque pièce est expédiée dans un écrin, en envoi suivi, assuré et remis contre signature, sous 2 à 5 jours ouvrés après le paiement.\n\n[Tarifs et délais par destination à vérifier.]\n\nPour les envois hors Union européenne, des droits de douane et taxes d'importation peuvent être demandés au destinataire.\n\n## Retours\n\nVous disposez de 14 jours après la réception pour nous retourner une pièce, sans avoir à vous justifier. Écrivez-nous à [adresse e-mail] avant le renvoi. Le bijou doit être renvoyé non porté, dans son écrin, en envoi assuré. Les frais de retour sont à votre charge. Nous vous remboursons dans les 14 jours suivant la réception du retour.",
                'body_en' => "## Shipping\n\nEvery piece ships in a gift box, tracked, insured and signed for on delivery, within 2 to 5 working days of payment.\n\n[Rates and times per destination to be checked.]\n\nFor deliveries outside the European Union, the recipient may have to pay import duties and taxes.\n\n## Returns\n\nYou have 14 days after delivery to return a piece, without giving a reason. Write to us at [email address] before sending it back. The piece must be unworn, in its box, sent insured. Return shipping is at your expense. We refund you within 14 days of receiving the return.",
            ],
            [
                'slug' => 'cgv',
                'title_fr' => 'Conditions générales de vente',
                'title_en' => 'Terms of sale',
                'position' => 2,
                'body_fr' => "[Modèle à compléter et à faire vérifier.]\n\n## 1. Vendeur\n\n[Nom], entrepreneur individuel (micro-entreprise), [adresse], SIRET [numéro]. TVA non applicable, art. 293 B du CGI. Contact : [e-mail].\n\n## 2. Produits\n\nPièces uniques, faites main. Les photos sont aussi fidèles que possible ; de légères variations de couleur liées à l'écran peuvent exister.\n\n## 3. Prix et paiement\n\nPrix en euros. Paiement par carte bancaire via Stripe. La commande est ferme après le paiement.\n\n## 4. Livraison\n\nVoir la page Livraison et retours.\n\n## 5. Droit de rétractation\n\n14 jours à compter de la réception (articles L221-18 et suivants du Code de la consommation). [Formulaire de rétractation à joindre.]\n\n## 6. Garanties légales\n\nGarantie légale de conformité (articles L217-3 et suivants du Code de la consommation) et garantie des vices cachés (articles 1641 et suivants du Code civil).\n\n## 7. Médiation\n\nEn cas de litige, vous pouvez recourir gratuitement au médiateur de la consommation : [nom et coordonnées du médiateur].\n\n## 8. Droit applicable\n\nDroit français.",
                'body_en' => "[Template to complete and have checked. The French version prevails.]\n\n## 1. Seller\n\n[Name], sole trader (micro-entreprise), [address], SIRET [number]. VAT not applicable, art. 293 B of the French tax code. Contact: [email].\n\n## 2. Products\n\nOne-of-a-kind, handmade pieces. Photos are as faithful as possible; slight colour differences due to screens may exist.\n\n## 3. Prices and payment\n\nPrices in euros. Card payment via Stripe. The order is final once paid.\n\n## 4. Delivery\n\nSee the Shipping and returns page.\n\n## 5. Right of withdrawal\n\n14 days from delivery. [Withdrawal form to attach.]\n\n## 6. Legal guarantees\n\nLegal guarantee of conformity and guarantee against hidden defects under French law.\n\n## 7. Mediation\n\nIn case of dispute you can use the consumer mediator free of charge: [mediator name and contact].\n\n## 8. Governing law\n\nFrench law.",
            ],
            [
                'slug' => 'mentions-legales',
                'title_fr' => 'Mentions légales',
                'title_en' => 'Legal notice',
                'position' => 3,
                'body_fr' => "[À compléter.]\n\n**Éditeur :** [Nom], entrepreneur individuel, [adresse], SIRET [numéro], [e-mail], [téléphone].\n\n**Directrice de la publication :** [Nom].\n\n**Hébergement :** OVH SAS, 2 rue Kellermann, 59100 Roubaix, France.",
                'body_en' => "[To complete.]\n\n**Publisher:** [Name], sole trader, [address], SIRET [number], [email], [phone].\n\n**Publication director:** [Name].\n\n**Hosting:** OVH SAS, 2 rue Kellermann, 59100 Roubaix, France.",
            ],
            [
                'slug' => 'confidentialite',
                'title_fr' => 'Données personnelles',
                'title_en' => 'Privacy',
                'position' => 4,
                'body_fr' => "[À compléter et à vérifier.]\n\nNous collectons uniquement les données nécessaires à votre commande (nom, e-mail, téléphone, adresse de livraison) et aux messages que vous nous envoyez. Le paiement est traité par Stripe ; nous n'avons jamais accès à vos données bancaires.\n\nCe site n'utilise ni cookies publicitaires ni outils de mesure d'audience. Il dépose seulement les cookies nécessaires au fonctionnement du panier et au choix de la langue.\n\nVos données de commande sont conservées le temps imposé par nos obligations comptables. Pour y accéder, les corriger ou les supprimer : [e-mail].",
                'body_en' => "[To complete and check.]\n\nWe only collect the data needed for your order (name, email, phone, shipping address) and for the messages you send us. Payment is handled by Stripe; we never see your card details.\n\nThis site uses no advertising cookies and no analytics. It only sets the cookies needed for the cart and your language choice.\n\nOrder data is kept for as long as our accounting obligations require. To access, correct or delete your data: [email].",
            ],
        ];

        foreach ($pages as $page) {
            Page::query()->firstOrCreate(['slug' => $page['slug']], $page);
        }
    }
}
