<?php

return [
    ['invoice', 'idx_invoice_id_user', '`id_user`(100)'],
    ['invoice', 'idx_invoice_username', '`username`(150)'],
    ['invoice', 'idx_invoice_status', '`Status`(100)'],
    ['invoice', 'idx_invoice_location', '`Service_location`(150)'],
    ['invoice', 'idx_invoice_id_payment', '`id_payment`'],
    ['payment_report', 'idx_payreport_id_user', '`id_user`(100)'],
    ['payment_report', 'idx_payreport_id_order', '`id_order`'],
    ['payment_report', 'idx_payreport_status', '`payment_Status`(100)'],
    ['marzban_panel', 'idx_panel_name', '`name_panel`(150)'],
    ['marzban_panel', 'idx_panel_code', '`code_panel`(100)'],
    ['product', 'idx_product_code', '`code_product`(50)'],
    ['product', 'idx_product_location', '`Location`(100)'],
    ['manualsell', 'idx_manualsell_codepanel', '`codepanel`(100)'],
    ['service_other', 'idx_serviceother_username', '`username`(150)'],
    ['service_other', 'idx_serviceother_type', '`type`(100)'],
    ['service_other', 'idx_serviceother_id_payment', '`id_payment`'],
    ['user', 'idx_user_affiliates', '`affiliates`'],
    ['departman', 'uniq_departman_entry', '`idsupport`(100), `name_departman`(150)', true],
];
