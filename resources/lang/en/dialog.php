<?php

return [
    'basic_headline' => 'We value your privacy',
    'basic_body' => 'We use cookies and similar technologies to enhance your browsing experience, analyze site traffic, and personalize content. You can customize your preferences at any time.',
    'customize_button_label' => 'Customize',
    'reject_button_label' => 'Reject All',
    'accept_button_label' => 'Accept All',
    'advanced_headline' => 'Cookie Preferences',
    'advanced_body' => 'Manage your cookie consent preferences.',
    'advanced_show_accept_reject' => true,
    'advanced_accept_button_label' => 'Accept All',
    'advanced_reject_button_label' => 'Reject All',
    'advanced_save_button_label' => 'Save Preferences',
    'required_label' => 'Required',
    'show_services' => 'Show services',
    'hide_services' => 'Hide services',

    // Integration service descriptions
    'integrations' => [
        'google_tag' => [
            'name' => 'Google Tag',
            'description' => 'Tracks website usage and performance metrics for Google Analytics, Ads, and other Google services.',
            'service_name' => ':name (:purposes)',
            'purposes' => [
                'functionality' => 'Functionality',
                'personalization' => 'Personalization',
                'security' => 'Security',
                'analytics' => 'Analytics',
                'advertising' => 'Advertising',
            ],
            'storage_descriptions' => [
                'functionality_storage' => 'Stores preferences for website features, such as language settings.',
                'personalization_storage' => 'Stores information for personalized content, such as video recommendations.',
                'security_storage' => 'Stores information for authentication, fraud prevention, and other security features.',
                'analytics_storage' => 'Stores information to measure website visits and usage.',
                'ad_storage' => 'Stores information for advertising.',
                'ad_user_data' => 'Allows user data to be sent to Google for advertising.',
                'ad_personalization' => 'Allows personalized advertising.',
            ],
        ],
        'meta_pixel' => [
            'name' => 'Facebook Pixel',
            'description' => 'Tracks conversions and user behavior for Facebook advertising campaigns.',
        ],
        'linkedin_insight' => [
            'name' => 'LinkedIn Insight',
            'description' => 'Tracks website visits and conversions for LinkedIn advertising.',
        ],
    ],
];
