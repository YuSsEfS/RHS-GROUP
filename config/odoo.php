<?php

return [

    'enabled' => env('ODOO_ENABLED', false),

    'url' => env('ODOO_URL'),

    'db' => env('ODOO_DB'),

    'username' => env('ODOO_USERNAME'),

    'api_key' => env('ODOO_API_KEY'),

    'preselection_model' => env('ODOO_PRESELECTION_MODEL', 'hr.applicant'),

    'applicant_model' => env('ODOO_APPLICANT_MODEL', 'hr.applicant'),

    'attachment_model' => env('ODOO_ATTACHMENT_MODEL', 'ir.attachment'),

    'timeout' => env('ODOO_TIMEOUT', 30),

    'verify_ssl' => env('ODOO_VERIFY_SSL', false),

    'client_model' => env('ODOO_CLIENT_MODEL', 'res.partner'),

    'department_model' => env('ODOO_DEPARTMENT_MODEL', 'hr.department'),

    'demande_model' => env('ODOO_DEMANDE_MODEL', 'hr.job'),

    'stage_model' => env('ODOO_STAGE_MODEL', 'hr.recruitment.stage'),

    'preselection_stage_name' => env('ODOO_PRESELECTION_STAGE_NAME', 'Nouveau CV à trier'),

    'preselection_stage_id' => env('ODOO_PRESELECTION_STAGE_ID', 1),

    'candidate_model' => env('ODOO_CANDIDATE_MODEL', 'hr.candidate'),

    'recruitment_url' => env('ODOO_RECRUITMENT_URL'),

    'recruitment_job_url_template' => env('ODOO_RECRUITMENT_JOB_URL_TEMPLATE'),
];
