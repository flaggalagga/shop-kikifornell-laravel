<?php

return [
    'nav' => [
        'skip' => 'Skip to content',
        'menu' => 'Menu',
        'main' => 'Main navigation',
        'footer' => 'Information',
        'pieces' => 'Pieces',
        'atelier' => 'About Kiki',
        'contact' => 'Contact',
        'back_to_top' => 'Back to top',
        'archive' => 'Sold pieces',
        'switch_to' => 'English',
        'cart' => 'Cart',
        'cart_items' => '{1} :count piece in your cart|[2,*] :count pieces in your cart',
    ],

    'home' => [
        'title' => 'Handcrafted jewellery',
        'cta' => 'Pieces',
        'see_collection' => 'See the collection',
        'collections' => 'Collections',
        'available' => 'Available now',
        'see_all' => 'All pieces',
    ],

    'promises' => [
        'label' => 'Our commitments',
        'unique_title' => 'Every piece is unique',
        'unique_text' => 'Each piece is enamelled and fired by hand. There is only one of each.',
        'hallmark_title' => 'Hallmarked',
        'hallmark_text' => "Precious-metal pieces carry the atelier's maker's mark.",
        'shipping_title' => 'Insured delivery',
        'shipping_text' => 'Tracked, insured and signed for on delivery, in a gift box.',
    ],

    'status' => [
        'sold' => 'Sold',
        'reserved' => 'Payment in progress',
    ],

    'piece' => [
        'photos' => 'Photos',
        'show_photo' => 'Show photo :n',
        'materials' => 'Materials',
        'dimensions' => 'Dimensions',
        'edition' => 'Edition',
        'unique' => 'One of a kind',
        'add_to_cart' => 'Add to cart',
        'in_cart' => 'In your cart',
        'shipping_note' => 'Insured shipping within 2 to 5 working days. Returns accepted within 14 days.',
        'reserved_note' => "Someone is paying for this piece right now. If the payment doesn't go through, it will be available again within half an hour.",
        'sold_note' => 'This piece has found its home. Kiki can make a new piece in the same spirit.',
        'ask_commission' => 'Ask for a commission',
        'related' => 'From the same collection',
    ],

    'inquiry' => [
        'title' => 'A question about this piece?',
        'intro' => 'Size, how it wears, a commission: write to Kiki and she will answer you personally.',
        'name' => 'Your name',
        'email' => 'Your email',
        'message' => 'Your message',
        'send' => 'Send message',
        'sent' => 'Message sent. Kiki will reply to you by email soon.',
    ],

    'pieces' => [
        'title' => 'Pieces',
        'intro' => 'Every piece available right now. Each one exists only once.',
        'empty' => 'No pieces are available right now: a new series is in the kiln.',
        'see_archive' => 'See sold pieces',
    ],

    'archive' => [
        'title' => 'Sold pieces',
        'intro' => 'Pieces that have already found a home. For a commission in the same spirit, write to the atelier.',
        'empty' => 'No sold pieces yet.',
    ],

    'collection' => [
        'empty' => 'Every piece in this collection has been sold.',
        'sold_count' => '{1} See the piece already sold|[2,*] See the :count pieces already sold',
    ],

    'cart' => [
        'title' => 'Cart',
        'empty' => 'Your cart is empty.',
        'added' => '“:title” is in your cart.',
        'not_available' => 'Piece :ref is no longer available.',
        'no_longer_available' => 'This piece is no longer available. Remove it to continue.',
        'remove' => 'Remove',
        'destination' => 'Delivery destination',
        'update_destination' => 'Update',
        'subtotal' => 'Jewellery',
        'shipping' => 'Delivery',
        'choose_destination' => 'Choose a destination',
        'total' => 'Total',
        'checkout' => 'Pay',
        'checkout_note' => 'Secure payment by Stripe. Your pieces are held for you for :minutes minutes.',
    ],

    'checkout' => [
        'terms_notice' => 'By paying, you accept our [terms of sale](:url).',
        'unavailable' => 'Sorry, these pieces have just been reserved or sold: :refs. Remove them from your cart to continue.',
        'failed' => "The payment couldn't start. Try again in a moment; if it keeps happening, write to us.",
        'cancelled' => 'Payment cancelled. Your pieces are still in your cart.',
        'thanks_title' => 'Thank you for your order',
        'thanks_order' => 'Your order :number is confirmed. A summary has been sent to :email.',
        'thanks_pending' => 'Your payment has been received. You will get the confirmation by email in a few minutes.',
        'thanks_next' => 'Kiki is preparing your piece and will send you the tracking number when it ships.',
        'back_home' => 'Back to the home page',
    ],

    'mail' => [
        'confirmation_subject' => 'Your order :number',
        'confirmation_title' => 'Thank you for your order',
        'confirmation_intro' => 'Hello :name, your payment has been received. Here is a summary of your order.',
        'piece' => 'Piece',
        'price' => 'Price',
        'order_number' => 'Order number',
        'ship_to' => 'Shipping to',
        'confirmation_next' => 'Your piece will be carefully packed and shipped within 2 to 5 working days. You will receive the tracking number by email. If you have any question, just reply to this message.',
        'signature' => 'Warm regards,',
        'shipped_subject' => 'Your order :number is on its way',
        'shipped_title' => 'Your piece is on its way',
        'shipped_intro' => 'Hello :name, your order :number has just left the atelier.',
        'carrier' => 'Carrier',
        'tracking_number' => 'Tracking number',
        'track' => 'Track the parcel',
        'shipped_outro' => 'Delivery needs a signature. If you have any question, just reply to this message.',
    ],

    'errors' => [
        'not_found_title' => 'Page not found',
        'not_found_text' => "This page doesn't exist any more. The piece you are looking for may have been sold.",
    ],
];
