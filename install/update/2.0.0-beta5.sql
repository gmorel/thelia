# This is a fix for InnoDB in MySQL >= 4.1.x
# It "suspends judgement" for fkey relationships until are tables are set.
SET FOREIGN_KEY_CHECKS = 0;

ALTER TABLE `product` CHANGE `position` `position` INT( 11 ) NOT NULL DEFAULT '0';
ALTER TABLE `product_version` CHANGE `position` `position` INT( 11 ) NOT NULL DEFAULT '0';

SELECT @max := MAX(`id`) FROM `message`;
SET @max := @max+1;

INSERT INTO `message` (`id`, `name`, `secured`, `created_at`, `updated_at`, `version`, `version_created_at`, `version_created_by`) VALUES
(@max, 'lost_password', 1, NOW(), NOW(), 2, NOW(), NULL);

INSERT INTO `message_i18n` (`id`, `locale`, `title`, `subject`, `text_message`, `html_message`) VALUES
(@max, 'en_US', 'Your new password', 'Your new password', 'Your new passord is : {$password}', '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">\r\n<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr" lang="fr">\r\n<head>\r\n<meta http-equiv="Content-Type" content="text/html; charset=utf-8"  />\r\n<title>changing password email for {config key="urlsite"} </title>\r\n{literal}\r\n<style type="text/css">\r\nbody {font-family: Arial, Helvetica, sans-serif;font-size:100%;text-align:center;}\r\n#liencompte {margin:25px 0 ;text-align: middle;font-size:10pt;}\r\n#wrapper {width:480pt;margin:0 auto;}\r\n#entete {padding-bottom:20px;margin-bottom:10px;border-bottom:1px dotted #000;}\r\n#logotexte {float:left;width:180pt;height:75pt;border:1pt solid #000;font-size:18pt;text-align:center;}\r\n#logoimg{float:left;}\r\n#h2 {margin:0;padding:0;font-size:140%;text-align:center;}\r\n#h3 {margin:0;padding:0;font-size:120%;text-align:center;}\r\n</style>\r\n{/literal}\r\n</head>\r\n<body>\r\n<div id="wrapper">\r\n<div id="entete">\r\n<h1 id="logotexte">{config key="store_name"}</h1>\r\n<h2 id="info">Changement de mot de passe</h2>\r\n<h5 id="mdp"> You have lost your password <br />\r\nYour new password is <span style="font-size:80%">{$password}</span>.</h5>\r\n</div>\r\n</div>\r\n<p id="liencompte">Vous  pouvez &agrave; pr&eacute;sent vous connecter sur <a href="{config key="urlsite"}">{config key="urlsite"}</a>.<br /> Please, change this password after your first connection</p>\r\n</body>\r\n</html>'),
(@max, 'fr_FR', 'Votre nouveau mot de passe', 'Votre nouveau mot de passe', 'Votre nouveau mot de passe est : {$password}', '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">\r\n<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr" lang="fr">\r\n<head>\r\n<meta http-equiv="Content-Type" content="text/html; charset=utf-8"  />\r\n<title>courriel de confirmation de changement de mot de passe de {config key="urlsite"} </title>\r\n{literal}\r\n<style type="text/css">\r\nbody {font-family: Arial, Helvetica, sans-serif;font-size:100%;text-align:center;}\r\n#liencompte {margin:25px 0 ;text-align: middle;font-size:10pt;}\r\n#wrapper {width:480pt;margin:0 auto;}\r\n#entete {padding-bottom:20px;margin-bottom:10px;border-bottom:1px dotted #000;}\r\n#logotexte {float:left;width:180pt;height:75pt;border:1pt solid #000;font-size:18pt;text-align:center;}\r\n#logoimg{float:left;}\r\n#h2 {margin:0;padding:0;font-size:140%;text-align:center;}\r\n#h3 {margin:0;padding:0;font-size:120%;text-align:center;}\r\n</style>\r\n{/literal}\r\n</head>\r\n<body>\r\n<div id="wrapper">\r\n<div id="entete">\r\n<h1 id="logotexte">{config key="store_name"}</h1>\r\n<h2 id="info">Changement de mot de passe</h2>\r\n<h5 id="mdp"> Vous avez perdu votre mot de passe. <br />\r\nVotre nouveau mot de passe est <span style="font-size:80%">{$password}</span>.</h5>\r\n</div>\r\n</div>\r\n<p id="liencompte">Vous  pouvez &agrave; pr&eacute;sent vous connecter sur <a href="{config key="urlsite"}">{config key="urlsite"}</a>.<br /> N''oubliez pas de modifier votre mot de passe.</p>\r\n</body>\r\n</html>');

ALTER TABLE `country` ADD INDEX `idx_country_by_default` (`by_default`);
ALTER TABLE `currency` ADD INDEX `idx_currency_by_default` (`by_default`);
ALTER TABLE `lang` ADD INDEX `idx_lang_by_default` (`by_default`);

ALTER TABLE `tax_rule_country` ADD INDEX `idx_tax_rule_country_tax_rule_id_country_id_position` (`tax_rule_id`, `country_id`, `position`);
ALTER TABLE `tax_rule_country` DROP INDEX `idx_tax_rule_country_tax_rule_id`;

ALTER TABLE `product_sale_elements` ADD INDEX `idx_product_sale_elements_promo_is_default` (`promo`, `is_default`);
ALTER TABLE `product_sale_elements` ADD INDEX `idx_product_sale_elements_newness_promo_is_default` (`newness`, `promo`, `is_default`);
ALTER TABLE `product_sale_elements` ADD INDEX `idx_product_sale_elements_product_id_id` (`product_id`, `id`);
ALTER TABLE `product_sale_elements` ADD INDEX `idx_product_elements_product_id_promo_is_default` (`product_id`, `promo`, `is_default`);
ALTER TABLE `product_sale_elements` ADD INDEX `idx_product_sale_elements_product_id_promo_id` (`product_id`, `promo`, `id`);
ALTER TABLE `product_sale_elements` DROP INDEX `idx_product_sale_element_product_id`;

ALTER TABLE `product_image` ADD INDEX `idx_product_image_product_id_position` (`product_id`, `position`);


ALTER TABLE `rewriting_url` ADD INDEX `idx_rewriting_url_view_updated_at` (`view`, `updated_at`);
ALTER TABLE `rewriting_url` ADD INDEX `idx_rewriting_url_view_id_view_view_locale_updated_at` (`view_id`, `view`, `view_locale`, `updated_at`);
ALTER TABLE `rewriting_url` DROP INDEX `idx_view_id` (`view_id`);

ALTER TABLE `feature_product` ADD INDEX `idx_feature_product_product_id_feature_id_position` (`product_id`, `feature_id`, `position`);
ALTER TABLE `feature_template` ADD INDEX `idx_feature_template_template_id_position` (`template_id`, `position`);

ALTER TABLE `currency` ADD INDEX `idx_currency_code` (`code`);



SET FOREIGN_KEY_CHECKS = 1;