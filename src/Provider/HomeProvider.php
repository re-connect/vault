<?php

namespace App\Provider;

class HomeProvider
{
    public const VAULT_FEATURES_CONTENT = [
        [
            'title' => 'cfn_complete_secure_title',
            'text' => 'cfn_complete_secure_p',
            'img_fileName' => 'feature1_vault.png',
            'img_alt' => 'cfn_complete_secure_alt_img',
            'box_shadow_class' => 'box-shadow-light-green',
            'is_mobile' => false,
        ],
        [
            'title' => 'cfn_accessible_tool_title',
            'text' => 'cfn_accessible_tool_p',
            'img_fileName' => 'feature2_vault.png',
            'img_alt' => 'cfn_accessible_tool_alt_img',
            'box_shadow_class' => 'box-shadow-light-green',
            'is_mobile' => true,
        ],
        [
            'title' => 'cfn_social_care_title',
            'text' => 'cfn_social_care_p',
            'img_fileName' => 'feature3_vault.png',
            'img_alt' => 'cfn_social_care_alt_img',
            'box_shadow_class' => 'box-shadow-light-green',
            'is_mobile' => false,
        ],
        [
            'title' => 'cfn_interface_social_workers_title',
            'text' => 'cfn_interface_social_workers_p',
            'gdpr_text' => 'gdpr_know_more',
            'img_fileName' => 'feature4_vault.png',
            'img_alt' => 'cfn_interface_social_workers_alt_img',
            'box_shadow_class' => 'box-shadow-light-blue',
            'is_mobile' => false,
        ],
    ];

    public const VAULT_IS_CONTENT = [
        [
            'icon_file_name' => 'icon_vault_is_safe.png',
            'icon_alt' => 'cfn_vault_is_safe_alt_img',
            'title' => 'cfn_vault_is_safe_title',
            'text' => 'cfn_vault_is_safe_p',
        ],
        [
            'icon_file_name' => 'icon_vault_exchange_info.png',
            'icon_alt' => 'cfn_vault_exchange_info_alt_img',
            'title' => 'cfn_vault_exchange_info_title',
            'text' => 'cfn_vault_exchange_info_p',
        ],
        [
            'icon_file_name' => 'icon_vault_easy_access.png',
            'icon_alt' => 'cfn_vault_easy_access_alt_img',
            'title' => 'cfn_vault_easy_access_title',
            'text' => 'cfn_vault_easy_access_p',
        ],
        [
            'icon_file_name' => 'logo_vault_like.png',
            'icon_alt' => 'cfn_vault_is_satisfying_alt_img',
            'title' => 'cfn_vault_is_satisfying_title',
            'text' => 'cfn_vault_is_satisfying_p',
        ],
    ];

    public const RP_FEATURES_CONTENT = [
        [
            'title' => 'rp_page_user_file_title',
            'text' => 'rp_page_user_file_p',
            'img_fileName' => 'feature1_rp.png',
            'img_alt' => 'rp_page_user_file_alt_img',
            'is_mobile' => false,
        ],
        [
            'title' => 'rp_page_info_transmission_title',
            'text' => 'rp_page_info_transmission_p',
            'img_fileName' => 'feature2_rp.png',
            'img_alt' => 'rp_page_info_transmission_alt_img',
            'is_mobile' => true,
        ],
        [
            'title' => 'rp_page_housing_management_title',
            'text' => 'rp_page_housing_management_p',
            'img_fileName' => 'feature3_rp.png',
            'img_alt' => 'rp_page_housing_management_alt_img',
            'is_mobile' => false,
        ],
        [
            'title' => 'rp_page_stats_automation_title',
            'text' => 'rp_page_stats_automation_p',
            'gdpr_text' => 'gdpr_know_more',
            'img_fileName' => 'feature4_rp.png',
            'img_alt' => 'rp_page_stats_automation_alt_img',
            'is_mobile' => false,
        ],
    ];

    public const RP_IS_CONTENT = [
        [
            'icon_file_name' => 'icon_rp_simple.png',
            'icon_alt' => 'rp_is_simple_alt_img',
            'title' => 'rp_is_simple_title',
            'text' => 'rp_is_simple_p',
        ],
        [
            'icon_file_name' => 'icon_rp_specific.png',
            'icon_alt' => 'rp_is_specific_alt_img',
            'title' => 'rp_is_specific_title',
            'text' => 'rp_is_specific_p',
        ],
        [
            'icon_file_name' => 'icon_rp_adaptable.png',
            'icon_alt' => 'rp_is_adaptable_alt_img',
            'title' => 'rp_is_adaptable_title',
            'text' => 'rp_is_adaptable_p',
        ],
        [
            'icon_file_name' => 'logo_rp_like.png',
            'icon_alt' => 'rp_is_satisfying_alt_img',
            'title' => 'rp_is_satisfying_title',
            'text' => 'rp_is_satisfying_p',
        ],
    ];

    public const DIGITAL_CARES_FEATURES_CONTENT = [
        [
            'title' => 'digital_cares_improve_reintegration_pathways_title',
            'text' => 'digital_cares_improve_reintegration_pathways_text',
            'img_fileName' => 'digital_cares_workshop_1.png',
            'img_alt' => 'digital_cares_improve_reintegration_pathways_img_alt',
            'is_mobile' => false,
        ],
        [
            'title' => 'digital_cares_personalized_title',
            'text' => 'digital_cares_personalized_text',
            'img_fileName' => 'digital_cares_workshop_2.png',
            'img_alt' => 'digital_cares_personalized_img_alt',
            'is_mobile' => false,
        ],
        [
            'title' => 'digital_cares_participatory_content_title',
            'text' => 'digital_cares_participatory_content_text',
            'img_fileName' => 'digital_cares_workshop_3.png',
            'img_alt' => 'digital_cares_participatory_content_img_alt',
            'is_mobile' => false,
        ],
        [
            'title' => 'digital_cares_scalable_content_title',
            'text' => 'digital_cares_scalable_content_text',
            'img_fileName' => 'digital_cares_workshop_4.png',
            'img_alt' => 'digital_cares_scalable_content_img_alt',
            'is_mobile' => false,
        ],
    ];

    public const DIGITAL_CARES_IS_CONTENT = [
        [
            'icon_file_name' => 'logo_digital_cares_like.png',
            'icon_alt' => 'digital_cares_appreciated_training_img_alt',
            'title' => 'digital_cares_appreciated_training_title',
            'text' => 'digital_cares_appreciated_training_text',
        ],
        [
            'icon_file_name' => 'logo_digital_cares_student.png',
            'icon_alt' => 'digital_cares_acquire_skills_img_alt',
            'title' => 'digital_cares_acquire_skills_title',
            'text' => 'digital_cares_acquire_skills_text',
        ],
        [
            'icon_file_name' => 'logo_digital_cares_bulb.png',
            'icon_alt' => 'digital_cares_dynamic_workshop_img_alt',
            'title' => 'digital_cares_dynamic_workshop_title',
            'text' => 'digital_cares_dynamic_workshop_text',
        ],
        [
            'icon_file_name' => 'logo_digital_cares_knowledge.png',
            'icon_alt' => 'digital_cares_knowledge_img_alt',
            'title' => 'digital_cares_knowledge_title',
            'text' => 'digital_cares_knowledge_text',
        ],
    ];
}
