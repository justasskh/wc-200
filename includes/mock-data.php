<?php
/**
 * Mock data for testing WooCommerce Gifting Flow
 * This file provides mock data for add-ons and greeting cards
 */

// Mock add-ons data
function wcflow_get_mock_addons() {
    return [
        [
            'id' => 'addon1',
            'title' => 'Gift Box',
            'price' => '£2.50',
            'price_value' => 2.50,
            'description' => 'A beautiful gift box to present your gift in. This premium gift box is made from recycled materials and comes with a ribbon. Perfect for any occasion, this gift box adds an extra special touch to your gift.',
            'img' => 'https://images.pexels.com/photos/264771/pexels-photo-264771.jpeg?auto=compress&cs=tinysrgb&w=400'
        ],
        [
            'id' => 'addon2',
            'title' => 'Chocolate Truffles',
            'price' => '£3.99',
            'price_value' => 3.99,
            'description' => 'Delicious chocolate truffles to accompany your gift. These handmade truffles are made with the finest Belgian chocolate and come in a variety of flavors. A perfect addition to any gift, these truffles are sure to delight.',
            'img' => 'https://images.pexels.com/photos/65882/chocolate-dark-coffee-confiserie-65882.jpeg?auto=compress&cs=tinysrgb&w=400'
        ],
        [
            'id' => 'addon3',
            'title' => 'Personalized Message Card',
            'price' => '£1.50',
            'price_value' => 1.50,
            'description' => 'Add a personalized message card to your gift. This card is printed on high-quality paper and can be customized with your own message. A thoughtful way to add a personal touch to your gift.',
            'img' => 'https://images.pexels.com/photos/6192337/pexels-photo-6192337.jpeg?auto=compress&cs=tinysrgb&w=400'
        ],
        [
            'id' => 'addon4',
            'title' => 'Gift Wrapping',
            'price' => '£2.00',
            'price_value' => 2.00,
            'description' => 'Professional gift wrapping service. Our expert gift wrappers will wrap your gift in high-quality wrapping paper and add a beautiful bow. Available in a variety of colors and patterns to suit any occasion.',
            'img' => 'https://images.pexels.com/photos/1666065/pexels-photo-1666065.jpeg?auto=compress&cs=tinysrgb&w=400'
        ]
    ];
}

// Mock greeting cards data
function wcflow_get_mock_cards() {
    return [
        'Birthday Cards' => [
            [
                'id' => 'card1',
                'title' => 'Happy Birthday',
                'price' => '£1.50',
                'price_value' => 1.50,
                'img' => 'https://images.pexels.com/photos/1729931/pexels-photo-1729931.jpeg?auto=compress&cs=tinysrgb&w=400'
            ],
            [
                'id' => 'card2',
                'title' => 'Birthday Wishes',
                'price' => '£1.50',
                'price_value' => 1.50,
                'img' => 'https://images.pexels.com/photos/1303080/pexels-photo-1303080.jpeg?auto=compress&cs=tinysrgb&w=400'
            ],
            [
                'id' => 'card3',
                'title' => 'Birthday Celebration',
                'price' => 'FREE',
                'price_value' => 0,
                'img' => 'https://images.pexels.com/photos/1793037/pexels-photo-1793037.jpeg?auto=compress&cs=tinysrgb&w=400'
            ]
        ],
        'Thank You Cards' => [
            [
                'id' => 'card4',
                'title' => 'Thank You',
                'price' => '£1.50',
                'price_value' => 1.50,
                'img' => 'https://images.pexels.com/photos/1108099/pexels-photo-1108099.jpeg?auto=compress&cs=tinysrgb&w=400'
            ],
            [
                'id' => 'card5',
                'title' => 'Gratitude',
                'price' => 'FREE',
                'price_value' => 0,
                'img' => 'https://images.pexels.com/photos/1449455/pexels-photo-1449455.jpeg?auto=compress&cs=tinysrgb&w=400'
            ]
        ]
    ];
}

// Hook into AJAX actions to provide mock data
add_action('wp_ajax_wcflow_get_addons', 'wcflow_mock_get_addons');
add_action('wp_ajax_nopriv_wcflow_get_addons', 'wcflow_mock_get_addons');

function wcflow_mock_get_addons() {
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wcflow_nonce')) {
        wp_send_json_error('Invalid nonce');
        return;
    }
    
    $addons = wcflow_get_mock_addons();
    wp_send_json_success($addons);
}

add_action('wp_ajax_wcflow_get_cards', 'wcflow_mock_get_cards');
add_action('wp_ajax_nopriv_wcflow_get_cards', 'wcflow_mock_get_cards');

function wcflow_mock_get_cards() {
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wcflow_nonce')) {
        wp_send_json_error('Invalid nonce');
        return;
    }
    
    $cards = wcflow_get_mock_cards();
    wp_send_json_success($cards);
}
