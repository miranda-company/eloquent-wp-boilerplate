-- MySQL dump 10.13  Distrib 8.0.35, for Win64 (x86_64)
--
-- Host: ::1    Database: local
-- ------------------------------------------------------
-- Server version	8.0.35

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `wp_actionscheduler_actions`
--

DROP TABLE IF EXISTS `wp_actionscheduler_actions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wp_actionscheduler_actions` (
  `action_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `hook` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `status` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `scheduled_date_gmt` datetime DEFAULT '0000-00-00 00:00:00',
  `scheduled_date_local` datetime DEFAULT '0000-00-00 00:00:00',
  `priority` tinyint unsigned NOT NULL DEFAULT '10',
  `args` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `schedule` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci,
  `group_id` bigint unsigned NOT NULL DEFAULT '0',
  `attempts` int NOT NULL DEFAULT '0',
  `last_attempt_gmt` datetime DEFAULT '0000-00-00 00:00:00',
  `last_attempt_local` datetime DEFAULT '0000-00-00 00:00:00',
  `claim_id` bigint unsigned NOT NULL DEFAULT '0',
  `extended_args` varchar(8000) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  PRIMARY KEY (`action_id`),
  KEY `hook_status_scheduled_date_gmt` (`hook`(163),`status`,`scheduled_date_gmt`),
  KEY `status_scheduled_date_gmt` (`status`,`scheduled_date_gmt`),
  KEY `scheduled_date_gmt` (`scheduled_date_gmt`),
  KEY `args` (`args`),
  KEY `group_id` (`group_id`),
  KEY `last_attempt_gmt` (`last_attempt_gmt`),
  KEY `claim_id_status_priority_scheduled_date_gmt` (`claim_id`,`status`,`priority`,`scheduled_date_gmt`),
  KEY `status_last_attempt_gmt` (`status`,`last_attempt_gmt`),
  KEY `status_claim_id` (`status`,`claim_id`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_actionscheduler_actions`
--

LOCK TABLES `wp_actionscheduler_actions` WRITE;
/*!40000 ALTER TABLE `wp_actionscheduler_actions` DISABLE KEYS */;
/*!40000 ALTER TABLE `wp_actionscheduler_actions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_actionscheduler_claims`
--

DROP TABLE IF EXISTS `wp_actionscheduler_claims`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wp_actionscheduler_claims` (
  `claim_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `date_created_gmt` datetime DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`claim_id`),
  KEY `date_created_gmt` (`date_created_gmt`)
) ENGINE=InnoDB AUTO_INCREMENT=133 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_actionscheduler_claims`
--

LOCK TABLES `wp_actionscheduler_claims` WRITE;
/*!40000 ALTER TABLE `wp_actionscheduler_claims` DISABLE KEYS */;
/*!40000 ALTER TABLE `wp_actionscheduler_claims` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_actionscheduler_groups`
--

DROP TABLE IF EXISTS `wp_actionscheduler_groups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wp_actionscheduler_groups` (
  `group_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `slug` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci NOT NULL,
  PRIMARY KEY (`group_id`),
  KEY `slug` (`slug`(191))
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_actionscheduler_groups`
--

LOCK TABLES `wp_actionscheduler_groups` WRITE;
/*!40000 ALTER TABLE `wp_actionscheduler_groups` DISABLE KEYS */;
/*!40000 ALTER TABLE `wp_actionscheduler_groups` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_actionscheduler_logs`
--

DROP TABLE IF EXISTS `wp_actionscheduler_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wp_actionscheduler_logs` (
  `log_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `action_id` bigint unsigned NOT NULL,
  `message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `log_date_gmt` datetime DEFAULT '0000-00-00 00:00:00',
  `log_date_local` datetime DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`log_id`),
  KEY `action_id` (`action_id`),
  KEY `log_date_gmt` (`log_date_gmt`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_actionscheduler_logs`
--

LOCK TABLES `wp_actionscheduler_logs` WRITE;
/*!40000 ALTER TABLE `wp_actionscheduler_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `wp_actionscheduler_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_commentmeta`
--

DROP TABLE IF EXISTS `wp_commentmeta`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wp_commentmeta` (
  `meta_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `comment_id` bigint unsigned NOT NULL DEFAULT '0',
  `meta_key` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `meta_value` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`meta_id`),
  KEY `comment_id` (`comment_id`),
  KEY `meta_key` (`meta_key`(191))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_commentmeta`
--

LOCK TABLES `wp_commentmeta` WRITE;
/*!40000 ALTER TABLE `wp_commentmeta` DISABLE KEYS */;
/*!40000 ALTER TABLE `wp_commentmeta` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_comments`
--

DROP TABLE IF EXISTS `wp_comments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wp_comments` (
  `comment_ID` bigint unsigned NOT NULL AUTO_INCREMENT,
  `comment_post_ID` bigint unsigned NOT NULL DEFAULT '0',
  `comment_author` tinytext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `comment_author_email` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
  `comment_author_url` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
  `comment_author_IP` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
  `comment_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `comment_date_gmt` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `comment_content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `comment_karma` int NOT NULL DEFAULT '0',
  `comment_approved` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '1',
  `comment_agent` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
  `comment_type` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'comment',
  `comment_parent` bigint unsigned NOT NULL DEFAULT '0',
  `user_id` bigint unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`comment_ID`),
  KEY `comment_post_ID` (`comment_post_ID`),
  KEY `comment_approved_date_gmt` (`comment_approved`,`comment_date_gmt`),
  KEY `comment_date_gmt` (`comment_date_gmt`),
  KEY `comment_parent` (`comment_parent`),
  KEY `comment_author_email` (`comment_author_email`(10))
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_comments`
--

LOCK TABLES `wp_comments` WRITE;
/*!40000 ALTER TABLE `wp_comments` DISABLE KEYS */;
/*!40000 ALTER TABLE `wp_comments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_ff_scheduled_actions`
--

DROP TABLE IF EXISTS `wp_ff_scheduled_actions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wp_ff_scheduled_actions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `action` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `form_id` bigint unsigned DEFAULT NULL,
  `origin_id` bigint unsigned DEFAULT NULL,
  `feed_id` bigint unsigned DEFAULT NULL,
  `type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci DEFAULT 'submission_action',
  `status` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci,
  `note` tinytext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci,
  `retry_count` int unsigned DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_ff_scheduled_actions`
--

LOCK TABLES `wp_ff_scheduled_actions` WRITE;
/*!40000 ALTER TABLE `wp_ff_scheduled_actions` DISABLE KEYS */;
/*!40000 ALTER TABLE `wp_ff_scheduled_actions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_fluentform_entry_details`
--

DROP TABLE IF EXISTS `wp_fluentform_entry_details`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wp_fluentform_entry_details` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `form_id` bigint unsigned DEFAULT NULL,
  `submission_id` bigint unsigned DEFAULT NULL,
  `field_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `sub_field_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `field_value` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_fluentform_entry_details`
--

LOCK TABLES `wp_fluentform_entry_details` WRITE;
/*!40000 ALTER TABLE `wp_fluentform_entry_details` DISABLE KEYS */;
/*!40000 ALTER TABLE `wp_fluentform_entry_details` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_fluentform_form_analytics`
--

DROP TABLE IF EXISTS `wp_fluentform_form_analytics`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wp_fluentform_form_analytics` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `form_id` int unsigned DEFAULT NULL,
  `user_id` int unsigned DEFAULT NULL,
  `source_url` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `platform` char(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `browser` char(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `city` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `country` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `ip` char(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `count` int DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `form_id_ip` (`form_id`,`ip`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_fluentform_form_analytics`
--

LOCK TABLES `wp_fluentform_form_analytics` WRITE;
/*!40000 ALTER TABLE `wp_fluentform_form_analytics` DISABLE KEYS */;
/*!40000 ALTER TABLE `wp_fluentform_form_analytics` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_fluentform_form_meta`
--

DROP TABLE IF EXISTS `wp_fluentform_form_meta`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wp_fluentform_form_meta` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `form_id` int unsigned DEFAULT NULL,
  `meta_key` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `value` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`id`),
  KEY `form_id_meta_key` (`form_id`,`meta_key`(191)),
  KEY `meta_key` (`meta_key`(191))
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_fluentform_form_meta`
--

LOCK TABLES `wp_fluentform_form_meta` WRITE;
/*!40000 ALTER TABLE `wp_fluentform_form_meta` DISABLE KEYS */;
INSERT INTO `wp_fluentform_form_meta` VALUES (1,1,'template_name','basic_contact_form');
INSERT INTO `wp_fluentform_form_meta` VALUES (2,1,'formSettings','{\"confirmation\":{\"redirectTo\":\"samePage\",\"messageToShow\":\"Thank you for your message. We will get in touch with you shortly\",\"customPage\":null,\"samePageFormBehavior\":\"hide_form\",\"customUrl\":null},\"restrictions\":{\"limitNumberOfEntries\":{\"enabled\":false,\"numberOfEntries\":null,\"period\":\"total\",\"limitReachedMsg\":\"Maximum number of entries exceeded.\"},\"scheduleForm\":{\"enabled\":false,\"start\":null,\"end\":null,\"selectedDays\":[\"Monday\",\"Tuesday\",\"Wednesday\",\"Thursday\",\"Friday\",\"Saturday\",\"Sunday\"],\"pendingMsg\":\"Form submission is not started yet.\",\"expiredMsg\":\"Form submission is now closed.\"},\"requireLogin\":{\"enabled\":false,\"requireLoginMsg\":\"You must be logged in to submit the form.\"},\"denyEmptySubmission\":{\"enabled\":false,\"message\":\"Sorry, you cannot submit an empty form. Let\'s hear what you wanna say.\"}},\"layout\":{\"labelPlacement\":\"top\",\"helpMessagePlacement\":\"with_label\",\"errorMessagePlacement\":\"inline\",\"cssClassName\":\"\",\"asteriskPlacement\":\"asterisk-right\"},\"delete_entry_on_submission\":\"no\",\"appendSurveyResult\":{\"enabled\":false,\"showLabel\":false,\"showCount\":false}}');
INSERT INTO `wp_fluentform_form_meta` VALUES (3,1,'advancedValidationSettings','{\"status\":false,\"type\":\"all\",\"conditions\":[{\"field\":\"\",\"operator\":\"=\",\"value\":\"\"}],\"error_message\":\"\",\"validation_type\":\"fail_on_condition_met\"}');
INSERT INTO `wp_fluentform_form_meta` VALUES (4,1,'double_optin_settings','{\"status\":\"no\",\"confirmation_message\":\"Please check your email inbox to confirm this submission\",\"email_body_type\":\"global\",\"email_subject\":\"Please confirm your form submission\",\"email_body\":\"<h2>Please Confirm Your Submission</h2><p>&nbsp;</p><p style=\"text-align: center;\"><a style=\"color: #ffffff; background-color: #454545; font-size: 16px; border-radius: 5px; text-decoration: none; font-weight: normal; font-style: normal; padding: 0.8rem 1rem; border-color: #0072ff;\" href=\"#confirmation_url#\">Confirm Submission</a></p><p>&nbsp;</p><p>If you received this email by mistake, simply delete it. Your form submission won\'t proceed if you don\'t click the confirmation link above.</p>\",\"email_field\":\"\",\"skip_if_logged_in\":\"yes\",\"skip_if_fc_subscribed\":\"no\"}');
INSERT INTO `wp_fluentform_form_meta` VALUES (5,2,'template_name','inline_subscription');
INSERT INTO `wp_fluentform_form_meta` VALUES (6,2,'formSettings','{\"confirmation\":{\"redirectTo\":\"samePage\",\"messageToShow\":\"Thank you for your message. We will get in touch with you shortly\",\"customPage\":null,\"samePageFormBehavior\":\"hide_form\",\"customUrl\":null},\"restrictions\":{\"limitNumberOfEntries\":{\"enabled\":false,\"numberOfEntries\":null,\"period\":\"total\",\"limitReachedMsg\":\"Maximum number of entries exceeded.\"},\"scheduleForm\":{\"enabled\":false,\"start\":null,\"end\":null,\"pendingMsg\":\"Form submission is not started yet.\",\"expiredMsg\":\"Form submission is now closed.\"},\"requireLogin\":{\"enabled\":false,\"requireLoginMsg\":\"You must be logged in to submit the form.\"},\"denyEmptySubmission\":{\"enabled\":false,\"message\":\"Sorry, you cannot submit an empty form. Let\'s hear what you wanna say.\"}},\"layout\":{\"labelPlacement\":\"top\",\"helpMessagePlacement\":\"with_label\",\"errorMessagePlacement\":\"inline\",\"asteriskPlacement\":\"asterisk-right\"}}');
INSERT INTO `wp_fluentform_form_meta` VALUES (7,2,'notifications','{\"name\":\"Admin Notification Email\",\"sendTo\":{\"type\":\"email\",\"email\":\"{wp.admin_email}\",\"field\":\"email\",\"routing\":[{\"email\":null,\"field\":null,\"operator\":\"=\",\"value\":null}]},\"fromName\":\"\",\"fromEmail\":\"\",\"replyTo\":\"\",\"bcc\":\"\",\"subject\":\"[{inputs.names}] New Form Submission\",\"message\":\"<p>{all_data}<\\/p> <p>This form submitted at: {embed_post.permalink}<\\/p>\",\"conditionals\":{\"status\":false,\"type\":\"all\",\"conditions\":[{\"field\":null,\"operator\":\"=\",\"value\":null}]},\"enabled\":false,\"email_template\":\"\"}');
INSERT INTO `wp_fluentform_form_meta` VALUES (8,2,'step_data_persistency_status','no');
INSERT INTO `wp_fluentform_form_meta` VALUES (9,2,'_primary_email_field','email');
/*!40000 ALTER TABLE `wp_fluentform_form_meta` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_fluentform_forms`
--

DROP TABLE IF EXISTS `wp_fluentform_forms`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wp_fluentform_forms` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `status` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci DEFAULT 'Draft',
  `appearance_settings` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci,
  `form_fields` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci,
  `has_payment` tinyint(1) NOT NULL DEFAULT '0',
  `type` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `conditions` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci,
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_fluentform_forms`
--

LOCK TABLES `wp_fluentform_forms` WRITE;
/*!40000 ALTER TABLE `wp_fluentform_forms` DISABLE KEYS */;
INSERT INTO `wp_fluentform_forms` VALUES (1,'Contact Form Demo','published',NULL,'{\"fields\":[{\"index\":0,\"element\":\"input_name\",\"attributes\":{\"name\":\"names\",\"data-type\":\"name-element\"},\"settings\":{\"container_class\":\"\",\"admin_field_label\":\"Name\",\"conditional_logics\":[]},\"fields\":{\"first_name\":{\"element\":\"input_text\",\"attributes\":{\"type\":\"text\",\"name\":\"first_name\",\"value\":\"\",\"id\":\"\",\"class\":\"\",\"placeholder\":\"First Name\"},\"settings\":{\"container_class\":\"\",\"label\":\"First Name\",\"help_message\":\"\",\"visible\":true,\"validation_rules\":{\"required\":{\"value\":false,\"message\":\"This field is required\"}},\"conditional_logics\":[]},\"editor_options\":{\"template\":\"inputText\"}},\"middle_name\":{\"element\":\"input_text\",\"attributes\":{\"type\":\"text\",\"name\":\"middle_name\",\"value\":\"\",\"id\":\"\",\"class\":\"\",\"placeholder\":\"\",\"required\":false},\"settings\":{\"container_class\":\"\",\"label\":\"Middle Name\",\"help_message\":\"\",\"error_message\":\"\",\"visible\":false,\"validation_rules\":{\"required\":{\"value\":false,\"message\":\"This field is required\"}},\"conditional_logics\":[]},\"editor_options\":{\"template\":\"inputText\"}},\"last_name\":{\"element\":\"input_text\",\"attributes\":{\"type\":\"text\",\"name\":\"last_name\",\"value\":\"\",\"id\":\"\",\"class\":\"\",\"placeholder\":\"Last Name\",\"required\":false},\"settings\":{\"container_class\":\"\",\"label\":\"Last Name\",\"help_message\":\"\",\"error_message\":\"\",\"visible\":true,\"validation_rules\":{\"required\":{\"value\":false,\"message\":\"This field is required\"}},\"conditional_logics\":[]},\"editor_options\":{\"template\":\"inputText\"}}},\"editor_options\":{\"title\":\"Name Fields\",\"element\":\"name-fields\",\"icon_class\":\"ff-edit-name\",\"template\":\"nameFields\"},\"uniqElKey\":\"el_1570866006692\"},{\"index\":1,\"element\":\"input_email\",\"attributes\":{\"type\":\"email\",\"name\":\"email\",\"value\":\"\",\"id\":\"\",\"class\":\"\",\"placeholder\":\"Email Address\"},\"settings\":{\"container_class\":\"\",\"label\":\"Email\",\"label_placement\":\"\",\"help_message\":\"\",\"admin_field_label\":\"\",\"validation_rules\":{\"required\":{\"value\":true,\"message\":\"This field is required\"},\"email\":{\"value\":true,\"message\":\"This field must contain a valid email\"}},\"conditional_logics\":[]},\"editor_options\":{\"title\":\"Email Address\",\"icon_class\":\"ff-edit-email\",\"template\":\"inputText\"},\"uniqElKey\":\"el_1570866012914\"},{\"index\":2,\"element\":\"input_text\",\"attributes\":{\"type\":\"text\",\"name\":\"subject\",\"value\":\"\",\"class\":\"\",\"placeholder\":\"Subject\"},\"settings\":{\"container_class\":\"\",\"label\":\"Subject\",\"label_placement\":\"\",\"admin_field_label\":\"Subject\",\"help_message\":\"\",\"validation_rules\":{\"required\":{\"value\":false,\"message\":\"This field is required\"}},\"conditional_logics\":{\"type\":\"any\",\"status\":false,\"conditions\":[{\"field\":\"\",\"value\":\"\",\"operator\":\"\"}]}},\"editor_options\":{\"title\":\"Simple Text\",\"icon_class\":\"ff-edit-text\",\"template\":\"inputText\"},\"uniqElKey\":\"el_1570878958648\"},{\"index\":3,\"element\":\"textarea\",\"attributes\":{\"name\":\"message\",\"value\":\"\",\"id\":\"\",\"class\":\"\",\"placeholder\":\"Your Message\",\"rows\":4,\"cols\":2},\"settings\":{\"container_class\":\"\",\"label\":\"Your Message\",\"admin_field_label\":\"\",\"label_placement\":\"\",\"help_message\":\"\",\"validation_rules\":{\"required\":{\"value\":true,\"message\":\"This field is required\"}},\"conditional_logics\":{\"type\":\"any\",\"status\":false,\"conditions\":[{\"field\":\"\",\"value\":\"\",\"operator\":\"\"}]}},\"editor_options\":{\"title\":\"Text Area\",\"icon_class\":\"ff-edit-textarea\",\"template\":\"inputTextarea\"},\"uniqElKey\":\"el_1570879001207\"}],\"submitButton\":{\"uniqElKey\":\"el_1524065200616\",\"element\":\"button\",\"attributes\":{\"type\":\"submit\",\"class\":\"\"},\"settings\":{\"align\":\"left\",\"button_style\":\"default\",\"container_class\":\"\",\"help_message\":\"\",\"background_color\":\"#1a7efb\",\"button_size\":\"md\",\"color\":\"#ffffff\",\"button_ui\":{\"type\":\"default\",\"text\":\"Submit Form\",\"img_url\":\"\"}},\"editor_options\":{\"title\":\"Submit Button\"}}}',0,'',NULL,1,'2026-05-14 06:01:17','2026-05-14 06:01:17');
INSERT INTO `wp_fluentform_forms` VALUES (2,'Subscription Form','published',NULL,'{\"fields\":[{\"index\":1,\"element\":\"container\",\"attributes\":[],\"settings\":{\"container_class\":\"\",\"conditional_logics\":{\"type\":\"any\",\"status\":false,\"conditions\":[{\"field\":\"\",\"value\":\"\",\"operator\":\"\"}]}},\"columns\":[{\"fields\":[{\"index\":1,\"element\":\"input_email\",\"attributes\":{\"type\":\"email\",\"name\":\"email\",\"value\":\"\",\"id\":\"\",\"class\":\"\",\"placeholder\":\"Your Email Address\"},\"settings\":{\"container_class\":\"\",\"label\":\"\",\"label_placement\":\"\",\"help_message\":\"\",\"admin_field_label\":\"Email\",\"validation_rules\":{\"required\":{\"value\":true,\"message\":\"This field is required\"},\"email\":{\"value\":true,\"message\":\"This field must contain a valid email\"}},\"conditional_logics\":[],\"is_unique\":\"no\",\"unique_validation_message\":\"Email address need to be unique.\"},\"editor_options\":{\"title\":\"Email Address\",\"icon_class\":\"ff-edit-email\",\"template\":\"inputText\"},\"uniqElKey\":\"el_16231279686950.8779857923682932\"}]},{\"fields\":[{\"index\":15,\"element\":\"custom_submit_button\",\"attributes\":{\"class\":\"\",\"type\":\"submit\"},\"settings\":{\"button_style\":\"\",\"button_size\":\"md\",\"align\":\"left\",\"container_class\":\"\",\"current_state\":\"normal_styles\",\"background_color\":\"\",\"color\":\"\",\"hover_styles\":{\"backgroundColor\":\"#ffffff\",\"borderColor\":\"#1a7efb\",\"color\":\"#1a7efb\",\"borderRadius\":\"\",\"minWidth\":\"100%\"},\"normal_styles\":{\"backgroundColor\":\"#1a7efb\",\"borderColor\":\"#1a7efb\",\"color\":\"#ffffff\",\"borderRadius\":\"\",\"minWidth\":\"100%\"},\"button_ui\":{\"text\":\"Subscribe\",\"type\":\"default\",\"img_url\":\"\"},\"conditional_logics\":{\"type\":\"any\",\"status\":false,\"conditions\":[{\"field\":\"\",\"value\":\"\",\"operator\":\"\"}]}},\"editor_options\":{\"title\":\"Custom Submit Button\",\"icon_class\":\"dashicons dashicons-arrow-right-alt\",\"template\":\"customButton\"},\"uniqElKey\":\"el_16231279798380.5947400167493171\"}]}],\"editor_options\":{\"title\":\"Two Column Container\",\"icon_class\":\"ff-edit-column-2\"},\"uniqElKey\":\"el_16231279284710.40955091024524304\"}],\"submitButton\":{\"uniqElKey\":\"el_1524065200616\",\"element\":\"button\",\"attributes\":{\"type\":\"submit\",\"class\":\"\"},\"settings\":{\"align\":\"left\",\"button_style\":\"default\",\"container_class\":\"\",\"help_message\":\"\",\"background_color\":\"#1a7efb\",\"button_size\":\"md\",\"color\":\"#ffffff\",\"button_ui\":{\"type\":\"default\",\"text\":\"Subscribe\",\"img_url\":\"\"}},\"editor_options\":{\"title\":\"Submit Button\"}}}',0,'form',NULL,1,'2026-05-14 06:01:17','2026-05-14 06:01:17');
/*!40000 ALTER TABLE `wp_fluentform_forms` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_fluentform_logs`
--

DROP TABLE IF EXISTS `wp_fluentform_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wp_fluentform_logs` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `parent_source_id` int unsigned DEFAULT NULL,
  `source_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `source_id` int unsigned DEFAULT NULL,
  `component` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `status` char(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `description` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_fluentform_logs`
--

LOCK TABLES `wp_fluentform_logs` WRITE;
/*!40000 ALTER TABLE `wp_fluentform_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `wp_fluentform_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_fluentform_submission_meta`
--

DROP TABLE IF EXISTS `wp_fluentform_submission_meta`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wp_fluentform_submission_meta` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `response_id` bigint unsigned DEFAULT NULL,
  `form_id` int unsigned DEFAULT NULL,
  `meta_key` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `value` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci,
  `status` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `user_id` int unsigned DEFAULT NULL,
  `name` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `response_id_meta_key` (`response_id`,`meta_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_fluentform_submission_meta`
--

LOCK TABLES `wp_fluentform_submission_meta` WRITE;
/*!40000 ALTER TABLE `wp_fluentform_submission_meta` DISABLE KEYS */;
/*!40000 ALTER TABLE `wp_fluentform_submission_meta` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_fluentform_submissions`
--

DROP TABLE IF EXISTS `wp_fluentform_submissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wp_fluentform_submissions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `form_id` int unsigned DEFAULT NULL,
  `serial_number` int unsigned DEFAULT NULL,
  `response` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci,
  `source_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `user_id` int unsigned DEFAULT NULL,
  `status` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci DEFAULT 'unread' COMMENT 'possible values: read, unread, trashed',
  `is_favourite` tinyint(1) NOT NULL DEFAULT '0',
  `browser` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `device` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `ip` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `city` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `country` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `payment_status` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `payment_method` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `payment_type` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `currency` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `payment_total` float DEFAULT NULL,
  `total_paid` float DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `form_id_status` (`form_id`,`status`),
  KEY `form_id_created_at` (`form_id`,`created_at`),
  KEY `user_id` (`user_id`),
  KEY `serial_number` (`serial_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_fluentform_submissions`
--

LOCK TABLES `wp_fluentform_submissions` WRITE;
/*!40000 ALTER TABLE `wp_fluentform_submissions` DISABLE KEYS */;
/*!40000 ALTER TABLE `wp_fluentform_submissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_links`
--

DROP TABLE IF EXISTS `wp_links`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wp_links` (
  `link_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `link_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
  `link_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
  `link_image` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
  `link_target` varchar(25) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
  `link_description` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
  `link_visible` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'Y',
  `link_owner` bigint unsigned NOT NULL DEFAULT '1',
  `link_rating` int NOT NULL DEFAULT '0',
  `link_updated` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `link_rel` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
  `link_notes` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `link_rss` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`link_id`),
  KEY `link_visible` (`link_visible`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_links`
--

LOCK TABLES `wp_links` WRITE;
/*!40000 ALTER TABLE `wp_links` DISABLE KEYS */;
/*!40000 ALTER TABLE `wp_links` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_options`
--

DROP TABLE IF EXISTS `wp_options`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wp_options` (
  `option_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `option_name` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
  `option_value` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `autoload` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'yes',
  PRIMARY KEY (`option_id`),
  UNIQUE KEY `option_name` (`option_name`),
  KEY `autoload` (`autoload`)
) ENGINE=InnoDB AUTO_INCREMENT=537 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_options`
--

LOCK TABLES `wp_options` WRITE;
/*!40000 ALTER TABLE `wp_options` DISABLE KEYS */;
INSERT INTO `wp_options` VALUES (1,'cron','a:13:{i:1780604657;a:1:{s:26:\"action_scheduler_run_queue\";a:1:{s:32:\"0d04ed39571b55704c122d726248bbac\";a:3:{s:8:\"schedule\";s:12:\"every_minute\";s:4:\"args\";a:1:{i:0;s:7:\"WP Cron\";}s:8:\"interval\";i:60;}}}i:1780604777;a:1:{s:29:\"fluentform_do_scheduled_tasks\";a:1:{s:32:\"40cd750bba9870f18aada2478b24840a\";a:3:{s:8:\"schedule\";s:21:\"ff_every_five_minutes\";s:4:\"args\";a:0:{}s:8:\"interval\";i:300;}}}i:1780605672;a:1:{s:16:\"wp_version_check\";a:1:{s:32:\"40cd750bba9870f18aada2478b24840a\";a:3:{s:8:\"schedule\";s:10:\"twicedaily\";s:4:\"args\";a:0:{}s:8:\"interval\";i:43200;}}}i:1780605673;a:1:{s:34:\"wp_privacy_delete_old_export_files\";a:1:{s:32:\"40cd750bba9870f18aada2478b24840a\";a:3:{s:8:\"schedule\";s:6:\"hourly\";s:4:\"args\";a:0:{}s:8:\"interval\";i:3600;}}}i:1780607472;a:1:{s:17:\"wp_update_plugins\";a:1:{s:32:\"40cd750bba9870f18aada2478b24840a\";a:3:{s:8:\"schedule\";s:10:\"twicedaily\";s:4:\"args\";a:0:{}s:8:\"interval\";i:43200;}}}i:1780609272;a:1:{s:16:\"wp_update_themes\";a:1:{s:32:\"40cd750bba9870f18aada2478b24840a\";a:3:{s:8:\"schedule\";s:10:\"twicedaily\";s:4:\"args\";a:0:{}s:8:\"interval\";i:43200;}}}i:1780645273;a:2:{s:32:\"recovery_mode_clean_expired_keys\";a:1:{s:32:\"40cd750bba9870f18aada2478b24840a\";a:3:{s:8:\"schedule\";s:5:\"daily\";s:4:\"args\";a:0:{}s:8:\"interval\";i:86400;}}s:30:\"wp_site_health_scheduled_check\";a:1:{s:32:\"40cd750bba9870f18aada2478b24840a\";a:3:{s:8:\"schedule\";s:6:\"weekly\";s:4:\"args\";a:0:{}s:8:\"interval\";i:604800;}}}i:1780645398;a:3:{s:19:\"wp_scheduled_delete\";a:1:{s:32:\"40cd750bba9870f18aada2478b24840a\";a:3:{s:8:\"schedule\";s:5:\"daily\";s:4:\"args\";a:0:{}s:8:\"interval\";i:86400;}}s:25:\"delete_expired_transients\";a:1:{s:32:\"40cd750bba9870f18aada2478b24840a\";a:3:{s:8:\"schedule\";s:5:\"daily\";s:4:\"args\";a:0:{}s:8:\"interval\";i:86400;}}s:21:\"wp_update_user_counts\";a:1:{s:32:\"40cd750bba9870f18aada2478b24840a\";a:3:{s:8:\"schedule\";s:10:\"twicedaily\";s:4:\"args\";a:0:{}s:8:\"interval\";i:43200;}}}i:1780645400;a:1:{s:30:\"wp_scheduled_auto_draft_delete\";a:1:{s:32:\"40cd750bba9870f18aada2478b24840a\";a:3:{s:8:\"schedule\";s:5:\"daily\";s:4:\"args\";a:0:{}s:8:\"interval\";i:86400;}}}i:1780646374;a:1:{s:27:\"acf_update_site_health_data\";a:1:{s:32:\"40cd750bba9870f18aada2478b24840a\";a:3:{s:8:\"schedule\";s:5:\"daily\";s:4:\"args\";a:0:{}s:8:\"interval\";i:86400;}}}i:1780646477;a:1:{s:42:\"fluentform_do_email_report_scheduled_tasks\";a:1:{s:32:\"40cd750bba9870f18aada2478b24840a\";a:3:{s:8:\"schedule\";s:5:\"daily\";s:4:\"args\";a:0:{}s:8:\"interval\";i:86400;}}}i:1781163909;a:1:{s:30:\"wp_delete_temp_updater_backups\";a:1:{s:32:\"40cd750bba9870f18aada2478b24840a\";a:3:{s:8:\"schedule\";s:6:\"weekly\";s:4:\"args\";a:0:{}s:8:\"interval\";i:604800;}}}s:7:\"version\";i:2;}','on');
INSERT INTO `wp_options` VALUES (2,'siteurl','http://llummio-wp-blueprint-workspace.local','on');
INSERT INTO `wp_options` VALUES (3,'home','http://llummio-wp-blueprint-workspace.local','on');
INSERT INTO `wp_options` VALUES (4,'blogname','Blueprint','on');
INSERT INTO `wp_options` VALUES (5,'blogdescription','','on');
INSERT INTO `wp_options` VALUES (6,'users_can_register','0','on');
INSERT INTO `wp_options` VALUES (7,'admin_email','info@llumm.io','on');
INSERT INTO `wp_options` VALUES (8,'start_of_week','1','on');
INSERT INTO `wp_options` VALUES (9,'use_balanceTags','0','on');
INSERT INTO `wp_options` VALUES (10,'use_smilies','1','on');
INSERT INTO `wp_options` VALUES (11,'require_name_email','1','on');
INSERT INTO `wp_options` VALUES (12,'comments_notify','1','on');
INSERT INTO `wp_options` VALUES (13,'posts_per_rss','10','on');
INSERT INTO `wp_options` VALUES (14,'rss_use_excerpt','0','on');
INSERT INTO `wp_options` VALUES (15,'mailserver_url','mail.example.com','on');
INSERT INTO `wp_options` VALUES (16,'mailserver_login','login@example.com','on');
INSERT INTO `wp_options` VALUES (17,'mailserver_pass','','on');
INSERT INTO `wp_options` VALUES (18,'mailserver_port','110','on');
INSERT INTO `wp_options` VALUES (19,'default_category','1','on');
INSERT INTO `wp_options` VALUES (20,'default_comment_status','open','on');
INSERT INTO `wp_options` VALUES (21,'default_ping_status','open','on');
INSERT INTO `wp_options` VALUES (22,'default_pingback_flag','1','on');
INSERT INTO `wp_options` VALUES (23,'posts_per_page','10','on');
INSERT INTO `wp_options` VALUES (24,'date_format','F j, Y','on');
INSERT INTO `wp_options` VALUES (25,'time_format','g:i a','on');
INSERT INTO `wp_options` VALUES (26,'links_updated_date_format','F j, Y g:i a','on');
INSERT INTO `wp_options` VALUES (27,'comment_moderation','0','on');
INSERT INTO `wp_options` VALUES (28,'moderation_notify','1','on');
INSERT INTO `wp_options` VALUES (29,'permalink_structure','/%postname%/','on');
INSERT INTO `wp_options` VALUES (30,'rewrite_rules','a:123:{s:11:\"^wp-json/?$\";s:22:\"index.php?rest_route=/\";s:14:\"^wp-json/(.*)?\";s:33:\"index.php?rest_route=/$matches[1]\";s:21:\"^index.php/wp-json/?$\";s:22:\"index.php?rest_route=/\";s:24:\"^index.php/wp-json/(.*)?\";s:33:\"index.php?rest_route=/$matches[1]\";s:17:\"^wp-sitemap\\.xml$\";s:23:\"index.php?sitemap=index\";s:17:\"^wp-sitemap\\.xsl$\";s:36:\"index.php?sitemap-stylesheet=sitemap\";s:23:\"^wp-sitemap-index\\.xsl$\";s:34:\"index.php?sitemap-stylesheet=index\";s:48:\"^wp-sitemap-([a-z]+?)-([a-z\\d_-]+?)-(\\d+?)\\.xml$\";s:75:\"index.php?sitemap=$matches[1]&sitemap-subtype=$matches[2]&paged=$matches[3]\";s:34:\"^wp-sitemap-([a-z]+?)-(\\d+?)\\.xml$\";s:47:\"index.php?sitemap=$matches[1]&paged=$matches[2]\";s:47:\"category/(.+?)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:52:\"index.php?category_name=$matches[1]&feed=$matches[2]\";s:42:\"category/(.+?)/(feed|rdf|rss|rss2|atom)/?$\";s:52:\"index.php?category_name=$matches[1]&feed=$matches[2]\";s:23:\"category/(.+?)/embed/?$\";s:46:\"index.php?category_name=$matches[1]&embed=true\";s:35:\"category/(.+?)/page/?([0-9]{1,})/?$\";s:53:\"index.php?category_name=$matches[1]&paged=$matches[2]\";s:17:\"category/(.+?)/?$\";s:35:\"index.php?category_name=$matches[1]\";s:44:\"tag/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:42:\"index.php?tag=$matches[1]&feed=$matches[2]\";s:39:\"tag/([^/]+)/(feed|rdf|rss|rss2|atom)/?$\";s:42:\"index.php?tag=$matches[1]&feed=$matches[2]\";s:20:\"tag/([^/]+)/embed/?$\";s:36:\"index.php?tag=$matches[1]&embed=true\";s:32:\"tag/([^/]+)/page/?([0-9]{1,})/?$\";s:43:\"index.php?tag=$matches[1]&paged=$matches[2]\";s:14:\"tag/([^/]+)/?$\";s:25:\"index.php?tag=$matches[1]\";s:45:\"type/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:50:\"index.php?post_format=$matches[1]&feed=$matches[2]\";s:40:\"type/([^/]+)/(feed|rdf|rss|rss2|atom)/?$\";s:50:\"index.php?post_format=$matches[1]&feed=$matches[2]\";s:21:\"type/([^/]+)/embed/?$\";s:44:\"index.php?post_format=$matches[1]&embed=true\";s:33:\"type/([^/]+)/page/?([0-9]{1,})/?$\";s:51:\"index.php?post_format=$matches[1]&paged=$matches[2]\";s:15:\"type/([^/]+)/?$\";s:33:\"index.php?post_format=$matches[1]\";s:68:\"gblocks_pattern_collections/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:66:\"index.php?gblocks_pattern_collections=$matches[1]&feed=$matches[2]\";s:63:\"gblocks_pattern_collections/([^/]+)/(feed|rdf|rss|rss2|atom)/?$\";s:66:\"index.php?gblocks_pattern_collections=$matches[1]&feed=$matches[2]\";s:44:\"gblocks_pattern_collections/([^/]+)/embed/?$\";s:60:\"index.php?gblocks_pattern_collections=$matches[1]&embed=true\";s:56:\"gblocks_pattern_collections/([^/]+)/page/?([0-9]{1,})/?$\";s:67:\"index.php?gblocks_pattern_collections=$matches[1]&paged=$matches[2]\";s:38:\"gblocks_pattern_collections/([^/]+)/?$\";s:49:\"index.php?gblocks_pattern_collections=$matches[1]\";s:45:\"gblocks_condition/[^/]+/attachment/([^/]+)/?$\";s:32:\"index.php?attachment=$matches[1]\";s:55:\"gblocks_condition/[^/]+/attachment/([^/]+)/trackback/?$\";s:37:\"index.php?attachment=$matches[1]&tb=1\";s:75:\"gblocks_condition/[^/]+/attachment/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:49:\"index.php?attachment=$matches[1]&feed=$matches[2]\";s:70:\"gblocks_condition/[^/]+/attachment/([^/]+)/(feed|rdf|rss|rss2|atom)/?$\";s:49:\"index.php?attachment=$matches[1]&feed=$matches[2]\";s:70:\"gblocks_condition/[^/]+/attachment/([^/]+)/comment-page-([0-9]{1,})/?$\";s:50:\"index.php?attachment=$matches[1]&cpage=$matches[2]\";s:51:\"gblocks_condition/[^/]+/attachment/([^/]+)/embed/?$\";s:43:\"index.php?attachment=$matches[1]&embed=true\";s:34:\"gblocks_condition/([^/]+)/embed/?$\";s:50:\"index.php?gblocks_condition=$matches[1]&embed=true\";s:38:\"gblocks_condition/([^/]+)/trackback/?$\";s:44:\"index.php?gblocks_condition=$matches[1]&tb=1\";s:46:\"gblocks_condition/([^/]+)/page/?([0-9]{1,})/?$\";s:57:\"index.php?gblocks_condition=$matches[1]&paged=$matches[2]\";s:53:\"gblocks_condition/([^/]+)/comment-page-([0-9]{1,})/?$\";s:57:\"index.php?gblocks_condition=$matches[1]&cpage=$matches[2]\";s:42:\"gblocks_condition/([^/]+)(?:/([0-9]+))?/?$\";s:56:\"index.php?gblocks_condition=$matches[1]&page=$matches[2]\";s:34:\"gblocks_condition/[^/]+/([^/]+)/?$\";s:32:\"index.php?attachment=$matches[1]\";s:44:\"gblocks_condition/[^/]+/([^/]+)/trackback/?$\";s:37:\"index.php?attachment=$matches[1]&tb=1\";s:64:\"gblocks_condition/[^/]+/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:49:\"index.php?attachment=$matches[1]&feed=$matches[2]\";s:59:\"gblocks_condition/[^/]+/([^/]+)/(feed|rdf|rss|rss2|atom)/?$\";s:49:\"index.php?attachment=$matches[1]&feed=$matches[2]\";s:59:\"gblocks_condition/[^/]+/([^/]+)/comment-page-([0-9]{1,})/?$\";s:50:\"index.php?attachment=$matches[1]&cpage=$matches[2]\";s:40:\"gblocks_condition/[^/]+/([^/]+)/embed/?$\";s:43:\"index.php?attachment=$matches[1]&embed=true\";s:62:\"gblocks_condition_cat/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:60:\"index.php?gblocks_condition_cat=$matches[1]&feed=$matches[2]\";s:57:\"gblocks_condition_cat/([^/]+)/(feed|rdf|rss|rss2|atom)/?$\";s:60:\"index.php?gblocks_condition_cat=$matches[1]&feed=$matches[2]\";s:38:\"gblocks_condition_cat/([^/]+)/embed/?$\";s:54:\"index.php?gblocks_condition_cat=$matches[1]&embed=true\";s:50:\"gblocks_condition_cat/([^/]+)/page/?([0-9]{1,})/?$\";s:61:\"index.php?gblocks_condition_cat=$matches[1]&paged=$matches[2]\";s:32:\"gblocks_condition_cat/([^/]+)/?$\";s:43:\"index.php?gblocks_condition_cat=$matches[1]\";s:12:\"robots\\.txt$\";s:18:\"index.php?robots=1\";s:13:\"favicon\\.ico$\";s:19:\"index.php?favicon=1\";s:12:\"sitemap\\.xml\";s:23:\"index.php?sitemap=index\";s:48:\".*wp-(atom|rdf|rss|rss2|feed|commentsrss2)\\.php$\";s:18:\"index.php?feed=old\";s:20:\".*wp-app\\.php(/.*)?$\";s:19:\"index.php?error=403\";s:18:\".*wp-register.php$\";s:23:\"index.php?register=true\";s:32:\"feed/(feed|rdf|rss|rss2|atom)/?$\";s:27:\"index.php?&feed=$matches[1]\";s:27:\"(feed|rdf|rss|rss2|atom)/?$\";s:27:\"index.php?&feed=$matches[1]\";s:8:\"embed/?$\";s:21:\"index.php?&embed=true\";s:20:\"page/?([0-9]{1,})/?$\";s:28:\"index.php?&paged=$matches[1]\";s:27:\"comment-page-([0-9]{1,})/?$\";s:38:\"index.php?&page_id=8&cpage=$matches[1]\";s:29:\"gb-template-viewer(/(.*))?/?$\";s:41:\"index.php?&gb-template-viewer=$matches[2]\";s:41:\"comments/feed/(feed|rdf|rss|rss2|atom)/?$\";s:42:\"index.php?&feed=$matches[1]&withcomments=1\";s:36:\"comments/(feed|rdf|rss|rss2|atom)/?$\";s:42:\"index.php?&feed=$matches[1]&withcomments=1\";s:17:\"comments/embed/?$\";s:21:\"index.php?&embed=true\";s:44:\"search/(.+)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:40:\"index.php?s=$matches[1]&feed=$matches[2]\";s:39:\"search/(.+)/(feed|rdf|rss|rss2|atom)/?$\";s:40:\"index.php?s=$matches[1]&feed=$matches[2]\";s:20:\"search/(.+)/embed/?$\";s:34:\"index.php?s=$matches[1]&embed=true\";s:32:\"search/(.+)/page/?([0-9]{1,})/?$\";s:41:\"index.php?s=$matches[1]&paged=$matches[2]\";s:14:\"search/(.+)/?$\";s:23:\"index.php?s=$matches[1]\";s:47:\"author/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:50:\"index.php?author_name=$matches[1]&feed=$matches[2]\";s:42:\"author/([^/]+)/(feed|rdf|rss|rss2|atom)/?$\";s:50:\"index.php?author_name=$matches[1]&feed=$matches[2]\";s:23:\"author/([^/]+)/embed/?$\";s:44:\"index.php?author_name=$matches[1]&embed=true\";s:35:\"author/([^/]+)/page/?([0-9]{1,})/?$\";s:51:\"index.php?author_name=$matches[1]&paged=$matches[2]\";s:17:\"author/([^/]+)/?$\";s:33:\"index.php?author_name=$matches[1]\";s:69:\"([0-9]{4})/([0-9]{1,2})/([0-9]{1,2})/feed/(feed|rdf|rss|rss2|atom)/?$\";s:80:\"index.php?year=$matches[1]&monthnum=$matches[2]&day=$matches[3]&feed=$matches[4]\";s:64:\"([0-9]{4})/([0-9]{1,2})/([0-9]{1,2})/(feed|rdf|rss|rss2|atom)/?$\";s:80:\"index.php?year=$matches[1]&monthnum=$matches[2]&day=$matches[3]&feed=$matches[4]\";s:45:\"([0-9]{4})/([0-9]{1,2})/([0-9]{1,2})/embed/?$\";s:74:\"index.php?year=$matches[1]&monthnum=$matches[2]&day=$matches[3]&embed=true\";s:57:\"([0-9]{4})/([0-9]{1,2})/([0-9]{1,2})/page/?([0-9]{1,})/?$\";s:81:\"index.php?year=$matches[1]&monthnum=$matches[2]&day=$matches[3]&paged=$matches[4]\";s:39:\"([0-9]{4})/([0-9]{1,2})/([0-9]{1,2})/?$\";s:63:\"index.php?year=$matches[1]&monthnum=$matches[2]&day=$matches[3]\";s:56:\"([0-9]{4})/([0-9]{1,2})/feed/(feed|rdf|rss|rss2|atom)/?$\";s:64:\"index.php?year=$matches[1]&monthnum=$matches[2]&feed=$matches[3]\";s:51:\"([0-9]{4})/([0-9]{1,2})/(feed|rdf|rss|rss2|atom)/?$\";s:64:\"index.php?year=$matches[1]&monthnum=$matches[2]&feed=$matches[3]\";s:32:\"([0-9]{4})/([0-9]{1,2})/embed/?$\";s:58:\"index.php?year=$matches[1]&monthnum=$matches[2]&embed=true\";s:44:\"([0-9]{4})/([0-9]{1,2})/page/?([0-9]{1,})/?$\";s:65:\"index.php?year=$matches[1]&monthnum=$matches[2]&paged=$matches[3]\";s:26:\"([0-9]{4})/([0-9]{1,2})/?$\";s:47:\"index.php?year=$matches[1]&monthnum=$matches[2]\";s:43:\"([0-9]{4})/feed/(feed|rdf|rss|rss2|atom)/?$\";s:43:\"index.php?year=$matches[1]&feed=$matches[2]\";s:38:\"([0-9]{4})/(feed|rdf|rss|rss2|atom)/?$\";s:43:\"index.php?year=$matches[1]&feed=$matches[2]\";s:19:\"([0-9]{4})/embed/?$\";s:37:\"index.php?year=$matches[1]&embed=true\";s:31:\"([0-9]{4})/page/?([0-9]{1,})/?$\";s:44:\"index.php?year=$matches[1]&paged=$matches[2]\";s:13:\"([0-9]{4})/?$\";s:26:\"index.php?year=$matches[1]\";s:27:\".?.+?/attachment/([^/]+)/?$\";s:32:\"index.php?attachment=$matches[1]\";s:37:\".?.+?/attachment/([^/]+)/trackback/?$\";s:37:\"index.php?attachment=$matches[1]&tb=1\";s:57:\".?.+?/attachment/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:49:\"index.php?attachment=$matches[1]&feed=$matches[2]\";s:52:\".?.+?/attachment/([^/]+)/(feed|rdf|rss|rss2|atom)/?$\";s:49:\"index.php?attachment=$matches[1]&feed=$matches[2]\";s:52:\".?.+?/attachment/([^/]+)/comment-page-([0-9]{1,})/?$\";s:50:\"index.php?attachment=$matches[1]&cpage=$matches[2]\";s:33:\".?.+?/attachment/([^/]+)/embed/?$\";s:43:\"index.php?attachment=$matches[1]&embed=true\";s:16:\"(.?.+?)/embed/?$\";s:41:\"index.php?pagename=$matches[1]&embed=true\";s:20:\"(.?.+?)/trackback/?$\";s:35:\"index.php?pagename=$matches[1]&tb=1\";s:40:\"(.?.+?)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:47:\"index.php?pagename=$matches[1]&feed=$matches[2]\";s:35:\"(.?.+?)/(feed|rdf|rss|rss2|atom)/?$\";s:47:\"index.php?pagename=$matches[1]&feed=$matches[2]\";s:28:\"(.?.+?)/page/?([0-9]{1,})/?$\";s:48:\"index.php?pagename=$matches[1]&paged=$matches[2]\";s:35:\"(.?.+?)/comment-page-([0-9]{1,})/?$\";s:48:\"index.php?pagename=$matches[1]&cpage=$matches[2]\";s:24:\"(.?.+?)(?:/([0-9]+))?/?$\";s:47:\"index.php?pagename=$matches[1]&page=$matches[2]\";s:27:\"[^/]+/attachment/([^/]+)/?$\";s:32:\"index.php?attachment=$matches[1]\";s:37:\"[^/]+/attachment/([^/]+)/trackback/?$\";s:37:\"index.php?attachment=$matches[1]&tb=1\";s:57:\"[^/]+/attachment/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:49:\"index.php?attachment=$matches[1]&feed=$matches[2]\";s:52:\"[^/]+/attachment/([^/]+)/(feed|rdf|rss|rss2|atom)/?$\";s:49:\"index.php?attachment=$matches[1]&feed=$matches[2]\";s:52:\"[^/]+/attachment/([^/]+)/comment-page-([0-9]{1,})/?$\";s:50:\"index.php?attachment=$matches[1]&cpage=$matches[2]\";s:33:\"[^/]+/attachment/([^/]+)/embed/?$\";s:43:\"index.php?attachment=$matches[1]&embed=true\";s:16:\"([^/]+)/embed/?$\";s:37:\"index.php?name=$matches[1]&embed=true\";s:20:\"([^/]+)/trackback/?$\";s:31:\"index.php?name=$matches[1]&tb=1\";s:40:\"([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:43:\"index.php?name=$matches[1]&feed=$matches[2]\";s:35:\"([^/]+)/(feed|rdf|rss|rss2|atom)/?$\";s:43:\"index.php?name=$matches[1]&feed=$matches[2]\";s:28:\"([^/]+)/page/?([0-9]{1,})/?$\";s:44:\"index.php?name=$matches[1]&paged=$matches[2]\";s:35:\"([^/]+)/comment-page-([0-9]{1,})/?$\";s:44:\"index.php?name=$matches[1]&cpage=$matches[2]\";s:24:\"([^/]+)(?:/([0-9]+))?/?$\";s:43:\"index.php?name=$matches[1]&page=$matches[2]\";s:16:\"[^/]+/([^/]+)/?$\";s:32:\"index.php?attachment=$matches[1]\";s:26:\"[^/]+/([^/]+)/trackback/?$\";s:37:\"index.php?attachment=$matches[1]&tb=1\";s:46:\"[^/]+/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:49:\"index.php?attachment=$matches[1]&feed=$matches[2]\";s:41:\"[^/]+/([^/]+)/(feed|rdf|rss|rss2|atom)/?$\";s:49:\"index.php?attachment=$matches[1]&feed=$matches[2]\";s:41:\"[^/]+/([^/]+)/comment-page-([0-9]{1,})/?$\";s:50:\"index.php?attachment=$matches[1]&cpage=$matches[2]\";s:22:\"[^/]+/([^/]+)/embed/?$\";s:43:\"index.php?attachment=$matches[1]&embed=true\";}','on');
INSERT INTO `wp_options` VALUES (31,'hack_file','0','on');
INSERT INTO `wp_options` VALUES (32,'blog_charset','UTF-8','on');
INSERT INTO `wp_options` VALUES (33,'moderation_keys','','off');
INSERT INTO `wp_options` VALUES (34,'active_plugins','a:6:{i:0;s:30:\"advanced-custom-fields/acf.php\";i:1;s:41:\"create-block-theme/create-block-theme.php\";i:2;s:25:\"fluentform/fluentform.php\";i:3;s:29:\"generateblocks-pro/plugin.php\";i:4;s:25:\"generateblocks/plugin.php\";i:5;s:43:\"llummio-svg-uploads/llummio-svg-uploads.php\";}','on');
INSERT INTO `wp_options` VALUES (35,'category_base','','on');
INSERT INTO `wp_options` VALUES (36,'ping_sites','https://rpc.pingomatic.com/','on');
INSERT INTO `wp_options` VALUES (37,'comment_max_links','2','on');
INSERT INTO `wp_options` VALUES (38,'gmt_offset','0','on');
INSERT INTO `wp_options` VALUES (39,'default_email_category','1','on');
INSERT INTO `wp_options` VALUES (40,'recently_edited','','off');
INSERT INTO `wp_options` VALUES (41,'template','llummio-blueprint','on');
INSERT INTO `wp_options` VALUES (42,'stylesheet','llummio-blueprint','on');
INSERT INTO `wp_options` VALUES (43,'comment_registration','0','on');
INSERT INTO `wp_options` VALUES (44,'html_type','text/html','on');
INSERT INTO `wp_options` VALUES (45,'use_trackback','0','on');
INSERT INTO `wp_options` VALUES (46,'default_role','subscriber','on');
INSERT INTO `wp_options` VALUES (47,'db_version','61833','on');
INSERT INTO `wp_options` VALUES (48,'uploads_use_yearmonth_folders','1','on');
INSERT INTO `wp_options` VALUES (49,'upload_path','','on');
INSERT INTO `wp_options` VALUES (50,'blog_public','1','on');
INSERT INTO `wp_options` VALUES (51,'default_link_category','2','on');
INSERT INTO `wp_options` VALUES (52,'show_on_front','page','on');
INSERT INTO `wp_options` VALUES (53,'tag_base','','on');
INSERT INTO `wp_options` VALUES (54,'show_avatars','1','on');
INSERT INTO `wp_options` VALUES (55,'avatar_rating','G','on');
INSERT INTO `wp_options` VALUES (56,'upload_url_path','','on');
INSERT INTO `wp_options` VALUES (57,'thumbnail_size_w','150','on');
INSERT INTO `wp_options` VALUES (58,'thumbnail_size_h','150','on');
INSERT INTO `wp_options` VALUES (59,'thumbnail_crop','1','on');
INSERT INTO `wp_options` VALUES (60,'medium_size_w','300','on');
INSERT INTO `wp_options` VALUES (61,'medium_size_h','300','on');
INSERT INTO `wp_options` VALUES (62,'avatar_default','mystery','on');
INSERT INTO `wp_options` VALUES (63,'large_size_w','1024','on');
INSERT INTO `wp_options` VALUES (64,'large_size_h','1024','on');
INSERT INTO `wp_options` VALUES (65,'image_default_link_type','none','on');
INSERT INTO `wp_options` VALUES (66,'image_default_size','','on');
INSERT INTO `wp_options` VALUES (67,'image_default_align','','on');
INSERT INTO `wp_options` VALUES (68,'close_comments_for_old_posts','0','on');
INSERT INTO `wp_options` VALUES (69,'close_comments_days_old','14','on');
INSERT INTO `wp_options` VALUES (70,'thread_comments','1','on');
INSERT INTO `wp_options` VALUES (71,'thread_comments_depth','5','on');
INSERT INTO `wp_options` VALUES (72,'page_comments','0','on');
INSERT INTO `wp_options` VALUES (73,'comments_per_page','50','on');
INSERT INTO `wp_options` VALUES (74,'default_comments_page','newest','on');
INSERT INTO `wp_options` VALUES (75,'comment_order','asc','on');
INSERT INTO `wp_options` VALUES (76,'sticky_posts','a:0:{}','on');
INSERT INTO `wp_options` VALUES (77,'widget_categories','a:0:{}','on');
INSERT INTO `wp_options` VALUES (78,'widget_text','a:0:{}','on');
INSERT INTO `wp_options` VALUES (79,'widget_rss','a:0:{}','on');
INSERT INTO `wp_options` VALUES (81,'timezone_string','','on');
INSERT INTO `wp_options` VALUES (82,'page_for_posts','0','on');
INSERT INTO `wp_options` VALUES (83,'page_on_front','8','on');
INSERT INTO `wp_options` VALUES (84,'default_post_format','0','on');
INSERT INTO `wp_options` VALUES (85,'link_manager_enabled','0','on');
INSERT INTO `wp_options` VALUES (86,'finished_splitting_shared_terms','1','on');
INSERT INTO `wp_options` VALUES (87,'site_icon','0','on');
INSERT INTO `wp_options` VALUES (88,'medium_large_size_w','768','on');
INSERT INTO `wp_options` VALUES (89,'medium_large_size_h','0','on');
INSERT INTO `wp_options` VALUES (90,'wp_page_for_privacy_policy','3','on');
INSERT INTO `wp_options` VALUES (91,'show_comments_cookies_opt_in','1','on');
INSERT INTO `wp_options` VALUES (92,'admin_email_lifespan','1794296472','on');
INSERT INTO `wp_options` VALUES (93,'disallowed_keys','','off');
INSERT INTO `wp_options` VALUES (94,'comment_previously_approved','1','on');
INSERT INTO `wp_options` VALUES (95,'auto_plugin_theme_update_emails','a:0:{}','off');
INSERT INTO `wp_options` VALUES (96,'auto_update_core_dev','enabled','on');
INSERT INTO `wp_options` VALUES (97,'auto_update_core_minor','enabled','on');
INSERT INTO `wp_options` VALUES (98,'auto_update_core_major','enabled','on');
INSERT INTO `wp_options` VALUES (99,'wp_force_deactivated_plugins','a:0:{}','on');
INSERT INTO `wp_options` VALUES (100,'wp_attachment_pages_enabled','0','on');
INSERT INTO `wp_options` VALUES (101,'wp_notes_notify','1','on');
INSERT INTO `wp_options` VALUES (102,'initial_db_version','60717','on');
INSERT INTO `wp_options` VALUES (103,'wp_user_roles','a:5:{s:13:\"administrator\";a:2:{s:4:\"name\";s:13:\"Administrator\";s:12:\"capabilities\";a:69:{s:13:\"switch_themes\";b:1;s:11:\"edit_themes\";b:1;s:16:\"activate_plugins\";b:1;s:12:\"edit_plugins\";b:1;s:10:\"edit_users\";b:1;s:10:\"edit_files\";b:1;s:14:\"manage_options\";b:1;s:17:\"moderate_comments\";b:1;s:17:\"manage_categories\";b:1;s:12:\"manage_links\";b:1;s:12:\"upload_files\";b:1;s:6:\"import\";b:1;s:15:\"unfiltered_html\";b:1;s:10:\"edit_posts\";b:1;s:17:\"edit_others_posts\";b:1;s:20:\"edit_published_posts\";b:1;s:13:\"publish_posts\";b:1;s:10:\"edit_pages\";b:1;s:4:\"read\";b:1;s:8:\"level_10\";b:1;s:7:\"level_9\";b:1;s:7:\"level_8\";b:1;s:7:\"level_7\";b:1;s:7:\"level_6\";b:1;s:7:\"level_5\";b:1;s:7:\"level_4\";b:1;s:7:\"level_3\";b:1;s:7:\"level_2\";b:1;s:7:\"level_1\";b:1;s:7:\"level_0\";b:1;s:17:\"edit_others_pages\";b:1;s:20:\"edit_published_pages\";b:1;s:13:\"publish_pages\";b:1;s:12:\"delete_pages\";b:1;s:19:\"delete_others_pages\";b:1;s:22:\"delete_published_pages\";b:1;s:12:\"delete_posts\";b:1;s:19:\"delete_others_posts\";b:1;s:22:\"delete_published_posts\";b:1;s:20:\"delete_private_posts\";b:1;s:18:\"edit_private_posts\";b:1;s:18:\"read_private_posts\";b:1;s:20:\"delete_private_pages\";b:1;s:18:\"edit_private_pages\";b:1;s:18:\"read_private_pages\";b:1;s:12:\"delete_users\";b:1;s:12:\"create_users\";b:1;s:17:\"unfiltered_upload\";b:1;s:14:\"edit_dashboard\";b:1;s:14:\"update_plugins\";b:1;s:14:\"delete_plugins\";b:1;s:15:\"install_plugins\";b:1;s:13:\"update_themes\";b:1;s:14:\"install_themes\";b:1;s:11:\"update_core\";b:1;s:10:\"list_users\";b:1;s:12:\"remove_users\";b:1;s:13:\"promote_users\";b:1;s:18:\"edit_theme_options\";b:1;s:13:\"delete_themes\";b:1;s:6:\"export\";b:1;s:27:\"fluentform_dashboard_access\";b:1;s:24:\"fluentform_forms_manager\";b:1;s:25:\"fluentform_entries_viewer\";b:1;s:25:\"fluentform_manage_entries\";b:1;s:24:\"fluentform_view_payments\";b:1;s:26:\"fluentform_manage_payments\";b:1;s:27:\"fluentform_settings_manager\";b:1;s:22:\"fluentform_full_access\";b:1;}}s:6:\"editor\";a:2:{s:4:\"name\";s:6:\"Editor\";s:12:\"capabilities\";a:34:{s:17:\"moderate_comments\";b:1;s:17:\"manage_categories\";b:1;s:12:\"manage_links\";b:1;s:12:\"upload_files\";b:1;s:15:\"unfiltered_html\";b:1;s:10:\"edit_posts\";b:1;s:17:\"edit_others_posts\";b:1;s:20:\"edit_published_posts\";b:1;s:13:\"publish_posts\";b:1;s:10:\"edit_pages\";b:1;s:4:\"read\";b:1;s:7:\"level_7\";b:1;s:7:\"level_6\";b:1;s:7:\"level_5\";b:1;s:7:\"level_4\";b:1;s:7:\"level_3\";b:1;s:7:\"level_2\";b:1;s:7:\"level_1\";b:1;s:7:\"level_0\";b:1;s:17:\"edit_others_pages\";b:1;s:20:\"edit_published_pages\";b:1;s:13:\"publish_pages\";b:1;s:12:\"delete_pages\";b:1;s:19:\"delete_others_pages\";b:1;s:22:\"delete_published_pages\";b:1;s:12:\"delete_posts\";b:1;s:19:\"delete_others_posts\";b:1;s:22:\"delete_published_posts\";b:1;s:20:\"delete_private_posts\";b:1;s:18:\"edit_private_posts\";b:1;s:18:\"read_private_posts\";b:1;s:20:\"delete_private_pages\";b:1;s:18:\"edit_private_pages\";b:1;s:18:\"read_private_pages\";b:1;}}s:6:\"author\";a:2:{s:4:\"name\";s:6:\"Author\";s:12:\"capabilities\";a:10:{s:12:\"upload_files\";b:1;s:10:\"edit_posts\";b:1;s:20:\"edit_published_posts\";b:1;s:13:\"publish_posts\";b:1;s:4:\"read\";b:1;s:7:\"level_2\";b:1;s:7:\"level_1\";b:1;s:7:\"level_0\";b:1;s:12:\"delete_posts\";b:1;s:22:\"delete_published_posts\";b:1;}}s:11:\"contributor\";a:2:{s:4:\"name\";s:11:\"Contributor\";s:12:\"capabilities\";a:5:{s:10:\"edit_posts\";b:1;s:4:\"read\";b:1;s:7:\"level_1\";b:1;s:7:\"level_0\";b:1;s:12:\"delete_posts\";b:1;}}s:10:\"subscriber\";a:2:{s:4:\"name\";s:10:\"Subscriber\";s:12:\"capabilities\";a:2:{s:4:\"read\";b:1;s:7:\"level_0\";b:1;}}}','on');
INSERT INTO `wp_options` VALUES (104,'fresh_site','0','off');
INSERT INTO `wp_options` VALUES (105,'user_count','1','off');
INSERT INTO `wp_options` VALUES (106,'widget_block','a:6:{i:2;a:1:{s:7:\"content\";s:19:\"<!-- wp:search /-->\";}i:3;a:1:{s:7:\"content\";s:154:\"<!-- wp:group --><div class=\"wp-block-group\"><!-- wp:heading --><h2>Recent Posts</h2><!-- /wp:heading --><!-- wp:latest-posts /--></div><!-- /wp:group -->\";}i:4;a:1:{s:7:\"content\";s:227:\"<!-- wp:group --><div class=\"wp-block-group\"><!-- wp:heading --><h2>Recent Comments</h2><!-- /wp:heading --><!-- wp:latest-comments {\"displayAvatar\":false,\"displayDate\":false,\"displayExcerpt\":false} /--></div><!-- /wp:group -->\";}i:5;a:1:{s:7:\"content\";s:146:\"<!-- wp:group --><div class=\"wp-block-group\"><!-- wp:heading --><h2>Archives</h2><!-- /wp:heading --><!-- wp:archives /--></div><!-- /wp:group -->\";}i:6;a:1:{s:7:\"content\";s:150:\"<!-- wp:group --><div class=\"wp-block-group\"><!-- wp:heading --><h2>Categories</h2><!-- /wp:heading --><!-- wp:categories /--></div><!-- /wp:group -->\";}s:12:\"_multiwidget\";i:1;}','auto');
INSERT INTO `wp_options` VALUES (107,'sidebars_widgets','a:2:{s:19:\"wp_inactive_widgets\";a:5:{i:0;s:7:\"block-2\";i:1;s:7:\"block-3\";i:2;s:7:\"block-4\";i:3;s:7:\"block-5\";i:4;s:7:\"block-6\";}s:13:\"array_version\";i:3;}','auto');
INSERT INTO `wp_options` VALUES (108,'widget_pages','a:1:{s:12:\"_multiwidget\";i:1;}','auto');
INSERT INTO `wp_options` VALUES (109,'widget_calendar','a:1:{s:12:\"_multiwidget\";i:1;}','auto');
INSERT INTO `wp_options` VALUES (110,'widget_archives','a:1:{s:12:\"_multiwidget\";i:1;}','auto');
INSERT INTO `wp_options` VALUES (111,'widget_media_audio','a:1:{s:12:\"_multiwidget\";i:1;}','auto');
INSERT INTO `wp_options` VALUES (112,'widget_media_image','a:1:{s:12:\"_multiwidget\";i:1;}','auto');
INSERT INTO `wp_options` VALUES (113,'widget_media_gallery','a:1:{s:12:\"_multiwidget\";i:1;}','auto');
INSERT INTO `wp_options` VALUES (114,'widget_media_video','a:1:{s:12:\"_multiwidget\";i:1;}','auto');
INSERT INTO `wp_options` VALUES (115,'widget_meta','a:1:{s:12:\"_multiwidget\";i:1;}','auto');
INSERT INTO `wp_options` VALUES (116,'widget_search','a:1:{s:12:\"_multiwidget\";i:1;}','auto');
INSERT INTO `wp_options` VALUES (117,'widget_recent-posts','a:1:{s:12:\"_multiwidget\";i:1;}','auto');
INSERT INTO `wp_options` VALUES (118,'widget_recent-comments','a:1:{s:12:\"_multiwidget\";i:1;}','auto');
INSERT INTO `wp_options` VALUES (119,'widget_tag_cloud','a:1:{s:12:\"_multiwidget\";i:1;}','auto');
INSERT INTO `wp_options` VALUES (120,'widget_nav_menu','a:1:{s:12:\"_multiwidget\";i:1;}','auto');
INSERT INTO `wp_options` VALUES (121,'widget_custom_html','a:1:{s:12:\"_multiwidget\";i:1;}','auto');
INSERT INTO `wp_options` VALUES (124,'WPLANG','','auto');
INSERT INTO `wp_options` VALUES (157,'generateblocks_dynamic_css_posts','a:0:{}','on');
INSERT INTO `wp_options` VALUES (158,'generateblocks_dynamic_css_time','1778744751','on');
INSERT INTO `wp_options` VALUES (159,'default_term_gblocks_pattern_collections','2','auto');
INSERT INTO `wp_options` VALUES (160,'generateblocks_version','2.2.1','auto');
INSERT INTO `wp_options` VALUES (161,'generateblocks_pro_version','2.6.0-beta.2','auto');
INSERT INTO `wp_options` VALUES (163,'generateblocks','a:7:{s:15:\"container_width\";i:1440;s:16:\"css_print_method\";s:6:\"inline\";s:24:\"sync_responsive_previews\";b:1;s:20:\"disable_google_fonts\";b:1;s:21:\"enable_overlay_panels\";b:1;s:23:\"enable_block_conditions\";b:1;s:12:\"enable_forms\";b:0;}','auto');
INSERT INTO `wp_options` VALUES (175,'generateblocks_active_overlays','a:0:{}','auto');
INSERT INTO `wp_options` VALUES (185,'acf_first_activated_version','6.8.1','on');
INSERT INTO `wp_options` VALUES (187,'acf_version','6.8.3','auto');
INSERT INTO `wp_options` VALUES (219,'action_scheduler_hybrid_store_demarkation','12','auto');
INSERT INTO `wp_options` VALUES (220,'schema-ActionScheduler_StoreSchema','8.0.1778745677','auto');
INSERT INTO `wp_options` VALUES (221,'schema-ActionScheduler_LoggerSchema','3.0.1778745677','auto');
INSERT INTO `wp_options` VALUES (222,'fluentform_entry_details_migrated','yes','off');
INSERT INTO `wp_options` VALUES (223,'fluentform_db_fluentform_logs_added','1','off');
INSERT INTO `wp_options` VALUES (224,'fluentform_scheduled_actions_migrated','yes','off');
INSERT INTO `wp_options` VALUES (225,'_fluentform_global_form_settings','a:2:{s:6:\"layout\";a:5:{s:14:\"labelPlacement\";s:3:\"top\";s:17:\"asteriskPlacement\";s:14:\"asterisk-right\";s:20:\"helpMessagePlacement\";s:10:\"with_label\";s:21:\"errorMessagePlacement\";s:6:\"inline\";s:12:\"cssClassName\";s:0:\"\";}s:4:\"misc\";a:5:{s:18:\"isIpLogingDisabled\";b:0;s:19:\"isAnalyticsDisabled\";b:1;s:21:\"file_upload_locations\";s:0:\"\";s:20:\"admin_top_nav_status\";s:3:\"yes\";s:23:\"default_admin_date_time\";s:9:\"time_diff\";}}','off');
INSERT INTO `wp_options` VALUES (226,'_fluentform_installed_version','6.2.2','off');
INSERT INTO `wp_options` VALUES (227,'fluentform_global_modules_status','a:9:{s:9:\"mailchimp\";s:2:\"no\";s:14:\"activecampaign\";s:2:\"no\";s:16:\"campaign_monitor\";s:2:\"no\";s:17:\"constatantcontact\";s:2:\"no\";s:11:\"getresponse\";s:2:\"no\";s:8:\"icontact\";s:2:\"no\";s:7:\"webhook\";s:2:\"no\";s:6:\"zapier\";s:2:\"no\";s:5:\"slack\";s:2:\"no\";}','off');
INSERT INTO `wp_options` VALUES (228,'action_scheduler_lock_async-request-runner','6a21decbde2fb8.65407985|1780604679','no');
INSERT INTO `wp_options` VALUES (229,'widget_fluentform_widget','a:1:{s:12:\"_multiwidget\";i:1;}','auto');
INSERT INTO `wp_options` VALUES (232,'fluentform_empty_manager_scopes_normalized','6.2.2','off');
INSERT INTO `wp_options` VALUES (237,'searchandfilter_version','1.2.17','auto');
INSERT INTO `wp_options` VALUES (239,'current_theme','llummio theme','auto');
INSERT INTO `wp_options` VALUES (240,'theme_mods_llummio-blueprint','a:4:{s:19:\"wp_classic_sidebars\";a:0:{}s:18:\"nav_menu_locations\";a:0:{}s:18:\"custom_css_post_id\";i:-1;s:16:\"sidebars_widgets\";a:2:{s:4:\"time\";i:1780583642;s:4:\"data\";a:1:{s:19:\"wp_inactive_widgets\";a:5:{i:0;s:7:\"block-2\";i:1;s:7:\"block-3\";i:2;s:7:\"block-4\";i:3;s:7:\"block-5\";i:4;s:7:\"block-6\";}}}}','off');
INSERT INTO `wp_options` VALUES (241,'theme_switched','','auto');
INSERT INTO `wp_options` VALUES (248,'generateblocks_style_css','','auto');
INSERT INTO `wp_options` VALUES (255,'nav_menu_options','a:2:{i:0;b:0;s:8:\"auto_add\";a:0:{}}','off');
INSERT INTO `wp_options` VALUES (293,'generateblocks_global_styles','a:0:{}','on');
INSERT INTO `wp_options` VALUES (318,'db_upgraded','','on');
INSERT INTO `wp_options` VALUES (321,'can_compress_scripts','0','on');
INSERT INTO `wp_options` VALUES (323,'as_has_wp_comment_logs','no','on');
INSERT INTO `wp_options` VALUES (328,'recovery_keys','a:0:{}','off');
INSERT INTO `wp_options` VALUES (330,'finished_updating_comment_type','1','auto');
INSERT INTO `wp_options` VALUES (342,'generateblocks_pro_classic_menu_support','','auto');
INSERT INTO `wp_options` VALUES (357,'action_scheduler_migration_status','complete','auto');
/*!40000 ALTER TABLE `wp_options` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_postmeta`
--

DROP TABLE IF EXISTS `wp_postmeta`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wp_postmeta` (
  `meta_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `post_id` bigint unsigned NOT NULL DEFAULT '0',
  `meta_key` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `meta_value` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`meta_id`),
  KEY `post_id` (`post_id`),
  KEY `meta_key` (`meta_key`(191))
) ENGINE=InnoDB AUTO_INCREMENT=64 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_postmeta`
--

LOCK TABLES `wp_postmeta` WRITE;
/*!40000 ALTER TABLE `wp_postmeta` DISABLE KEYS */;
INSERT INTO `wp_postmeta` VALUES (10,8,'_edit_lock','1780588070:1');
INSERT INTO `wp_postmeta` VALUES (18,20,'_menu_item_type','post_type');
INSERT INTO `wp_postmeta` VALUES (19,20,'_menu_item_menu_item_parent','0');
INSERT INTO `wp_postmeta` VALUES (20,20,'_menu_item_object_id','8');
INSERT INTO `wp_postmeta` VALUES (21,20,'_menu_item_object','page');
INSERT INTO `wp_postmeta` VALUES (22,20,'_menu_item_target','');
INSERT INTO `wp_postmeta` VALUES (23,20,'_menu_item_classes','a:1:{i:0;s:0:\"\";}');
INSERT INTO `wp_postmeta` VALUES (24,20,'_menu_item_xfn','');
INSERT INTO `wp_postmeta` VALUES (25,20,'_menu_item_url','');
INSERT INTO `wp_postmeta` VALUES (28,28,'wp_pattern_sync_status','unsynced');
INSERT INTO `wp_postmeta` VALUES (29,28,'generateblocks_patterns_tree','a:1:{i:0;a:9:{s:2:\"id\";s:10:\"pattern-28\";s:5:\"label\";s:9:\"Section S\";s:7:\"pattern\";s:1445:\"<!-- wp:generateblocks/element {\"uniqueId\":\"4e15e748\",\"tagName\":\"section\",\"styles\":{\"paddingTop\":\"var(\\u002d\\u002dpadding-section-s)\",\"paddingRight\":\"var(\\u002d\\u002dpadding-section-lateral-default)\",\"paddingBottom\":\"var(\\u002d\\u002dpadding-section-s)\",\"paddingLeft\":\"var(\\u002d\\u002dpadding-section-lateral-default)\",\"@media (max-width:767px)\":{\"paddingTop\":\"var(\\u002d\\u002dpadding-section-s-mobile)\",\"paddingRight\":\"var(\\u002d\\u002dpadding-section-lateral-default-mobile)\",\"paddingBottom\":\"var(\\u002d\\u002dpadding-section-s-mobile)\",\"paddingLeft\":\"var(\\u002d\\u002dpadding-section-lateral-default-mobile)\"}},\"css\":\".gb-element-4e15e748{padding:var(\\u002d\\u002dpadding-section-s) var(\\u002d\\u002dpadding-section-lateral-default)}@media (max-width:767px){.gb-element-4e15e748{padding:var(\\u002d\\u002dpadding-section-s-mobile) var(\\u002d\\u002dpadding-section-lateral-default-mobile)}}\",\"metadata\":{\"name\":\"Section S\"},\"className\":\"wire\"} -->\n<section class=\"gb-element-4e15e748 wire\"><!-- wp:generateblocks/element {\"uniqueId\":\"f65d3d9a\",\"tagName\":\"div\",\"styles\":{\"maxWidth\":\"var(\\u002d\\u002dgb-container-width)\",\"marginLeft\":\"auto\",\"marginRight\":\"auto\"},\"css\":\".gb-element-f65d3d9a{margin-left:auto;margin-right:auto;max-width:var(\\u002d\\u002dgb-container-width)}\",\"metadata\":{\"name\":\"Wrapper\"},\"className\":\"wire\"} -->\n<div class=\"gb-element-f65d3d9a wire\"></div>\n<!-- /wp:generateblocks/element --></section>\n<!-- /wp:generateblocks/element -->\";s:7:\"preview\";s:448:\"<style>.gb-element-4e15e748{padding:var(--padding-section-s) var(--padding-section-lateral-default)}@media (max-width:767px){.gb-element-4e15e748{padding:var(--padding-section-s-mobile) var(--padding-section-lateral-default-mobile)}}</style>\n<section class=\"gb-element-4e15e748 wire\"><style>.gb-element-f65d3d9a{margin-left:auto;margin-right:auto;max-width:var(--gb-container-width)}</style>\n<div class=\"gb-element-f65d3d9a wire\"></div>\n</section>\n\";s:7:\"scripts\";a:0:{}s:6:\"styles\";a:0:{}s:10:\"categories\";a:1:{i:0;i:8;}s:20:\"globalStyleSelectors\";a:0:{}s:8:\"formRefs\";a:0:{}}}');
INSERT INTO `wp_postmeta` VALUES (30,28,'_generateblocks_dynamic_css_version','2.2.1');
INSERT INTO `wp_postmeta` VALUES (31,8,'_generateblocks_dynamic_css_version','2.2.1');
INSERT INTO `wp_postmeta` VALUES (37,50,'wp_pattern_sync_status','unsynced');
INSERT INTO `wp_postmeta` VALUES (38,50,'generateblocks_patterns_tree','a:1:{i:0;a:9:{s:2:\"id\";s:10:\"pattern-50\";s:5:\"label\";s:10:\"Section XS\";s:7:\"pattern\";s:1469:\"<!-- wp:generateblocks/element {\"uniqueId\":\"4e15e748\",\"tagName\":\"section\",\"styles\":{\"paddingTop\":\"var(\\u002d\\u002dpadding-section-xs)\",\"paddingRight\":\"var(\\u002d\\u002dpadding-section-lateral-default)\",\"paddingBottom\":\"var(\\u002d\\u002dpadding-section-xs)\",\"paddingLeft\":\"var(\\u002d\\u002dpadding-section-lateral-default)\",\"@media (max-width:767px)\":{\"paddingTop\":\"var(\\u002d\\u002dpadding-section-xs-mobile)\",\"paddingRight\":\"var(\\u002d\\u002dpadding-section-lateral-default-mobile)\",\"paddingBottom\":\"var(\\u002d\\u002dpadding-section-xs-mobile)\",\"paddingLeft\":\"var(\\u002d\\u002dpadding-section-lateral-default-mobile)\"}},\"css\":\".gb-element-4e15e748{padding:var(\\u002d\\u002dpadding-section-xs) var(\\u002d\\u002dpadding-section-lateral-default)}@media (max-width:767px){.gb-element-4e15e748{padding:var(\\u002d\\u002dpadding-section-xs-mobile) var(\\u002d\\u002dpadding-section-lateral-default-mobile)}}\",\"metadata\":{\"name\":\"Section XS\",\"categories\":[8]},\"className\":\"wire\"} -->\n<section class=\"gb-element-4e15e748 wire\"><!-- wp:generateblocks/element {\"uniqueId\":\"f65d3d9a\",\"tagName\":\"div\",\"styles\":{\"maxWidth\":\"var(\\u002d\\u002dgb-container-width)\",\"marginLeft\":\"auto\",\"marginRight\":\"auto\"},\"css\":\".gb-element-f65d3d9a{margin-left:auto;margin-right:auto;max-width:var(\\u002d\\u002dgb-container-width)}\",\"metadata\":{\"name\":\"Wrapper\"},\"className\":\"wire\"} -->\n<div class=\"gb-element-f65d3d9a wire\"></div>\n<!-- /wp:generateblocks/element --></section>\n<!-- /wp:generateblocks/element -->\";s:7:\"preview\";s:450:\"<style>.gb-element-4e15e748{padding:var(--padding-section-xs) var(--padding-section-lateral-default)}@media (max-width:767px){.gb-element-4e15e748{padding:var(--padding-section-xs-mobile) var(--padding-section-lateral-default-mobile)}}</style>\n<section class=\"gb-element-4e15e748 wire\"><style>.gb-element-f65d3d9a{margin-left:auto;margin-right:auto;max-width:var(--gb-container-width)}</style>\n<div class=\"gb-element-f65d3d9a wire\"></div>\n</section>\n\";s:7:\"scripts\";a:0:{}s:6:\"styles\";a:0:{}s:10:\"categories\";a:1:{i:0;i:8;}s:20:\"globalStyleSelectors\";a:0:{}s:8:\"formRefs\";a:0:{}}}');
INSERT INTO `wp_postmeta` VALUES (39,50,'_generateblocks_dynamic_css_version','2.2.1');
INSERT INTO `wp_postmeta` VALUES (40,52,'wp_pattern_sync_status','unsynced');
INSERT INTO `wp_postmeta` VALUES (41,52,'generateblocks_patterns_tree','a:1:{i:0;a:9:{s:2:\"id\";s:10:\"pattern-52\";s:5:\"label\";s:11:\"Section XXS\";s:7:\"pattern\";s:1476:\"<!-- wp:generateblocks/element {\"uniqueId\":\"4e15e748\",\"tagName\":\"section\",\"styles\":{\"paddingTop\":\"var(\\u002d\\u002dpadding-section-xxs)\",\"paddingRight\":\"var(\\u002d\\u002dpadding-section-lateral-default)\",\"paddingBottom\":\"var(\\u002d\\u002dpadding-section-xxs)\",\"paddingLeft\":\"var(\\u002d\\u002dpadding-section-lateral-default)\",\"@media (max-width:767px)\":{\"paddingTop\":\"var(\\u002d\\u002dpadding-section-xxs-mobile)\",\"paddingRight\":\"var(\\u002d\\u002dpadding-section-lateral-default-mobile)\",\"paddingBottom\":\"var(\\u002d\\u002dpadding-section-xxs-mobile)\",\"paddingLeft\":\"var(\\u002d\\u002dpadding-section-lateral-default-mobile)\"}},\"css\":\".gb-element-4e15e748{padding:var(\\u002d\\u002dpadding-section-xxs) var(\\u002d\\u002dpadding-section-lateral-default)}@media (max-width:767px){.gb-element-4e15e748{padding:var(\\u002d\\u002dpadding-section-xxs-mobile) var(\\u002d\\u002dpadding-section-lateral-default-mobile)}}\",\"metadata\":{\"name\":\"Section XXS\",\"categories\":[8]},\"className\":\"wire\"} -->\n<section class=\"gb-element-4e15e748 wire\"><!-- wp:generateblocks/element {\"uniqueId\":\"f65d3d9a\",\"tagName\":\"div\",\"styles\":{\"maxWidth\":\"var(\\u002d\\u002dgb-container-width)\",\"marginLeft\":\"auto\",\"marginRight\":\"auto\"},\"css\":\".gb-element-f65d3d9a{margin-left:auto;margin-right:auto;max-width:var(\\u002d\\u002dgb-container-width)}\",\"metadata\":{\"name\":\"Wrapper\"},\"className\":\"wire\"} -->\n<div class=\"gb-element-f65d3d9a wire\"></div>\n<!-- /wp:generateblocks/element --></section>\n<!-- /wp:generateblocks/element -->\";s:7:\"preview\";s:452:\"<style>.gb-element-4e15e748{padding:var(--padding-section-xxs) var(--padding-section-lateral-default)}@media (max-width:767px){.gb-element-4e15e748{padding:var(--padding-section-xxs-mobile) var(--padding-section-lateral-default-mobile)}}</style>\n<section class=\"gb-element-4e15e748 wire\"><style>.gb-element-f65d3d9a{margin-left:auto;margin-right:auto;max-width:var(--gb-container-width)}</style>\n<div class=\"gb-element-f65d3d9a wire\"></div>\n</section>\n\";s:7:\"scripts\";a:0:{}s:6:\"styles\";a:0:{}s:10:\"categories\";a:1:{i:0;i:8;}s:20:\"globalStyleSelectors\";a:0:{}s:8:\"formRefs\";a:0:{}}}');
INSERT INTO `wp_postmeta` VALUES (42,52,'_generateblocks_dynamic_css_version','2.2.1');
INSERT INTO `wp_postmeta` VALUES (43,54,'wp_pattern_sync_status','unsynced');
INSERT INTO `wp_postmeta` VALUES (44,54,'generateblocks_patterns_tree','a:1:{i:0;a:9:{s:2:\"id\";s:10:\"pattern-54\";s:5:\"label\";s:9:\"Section M\";s:7:\"pattern\";s:1462:\"<!-- wp:generateblocks/element {\"uniqueId\":\"4e15e748\",\"tagName\":\"section\",\"styles\":{\"paddingTop\":\"var(\\u002d\\u002dpadding-section-m)\",\"paddingRight\":\"var(\\u002d\\u002dpadding-section-lateral-default)\",\"paddingBottom\":\"var(\\u002d\\u002dpadding-section-m)\",\"paddingLeft\":\"var(\\u002d\\u002dpadding-section-lateral-default)\",\"@media (max-width:767px)\":{\"paddingTop\":\"var(\\u002d\\u002dpadding-section-m-mobile)\",\"paddingRight\":\"var(\\u002d\\u002dpadding-section-lateral-default-mobile)\",\"paddingBottom\":\"var(\\u002d\\u002dpadding-section-m-mobile)\",\"paddingLeft\":\"var(\\u002d\\u002dpadding-section-lateral-default-mobile)\"}},\"css\":\".gb-element-4e15e748{padding:var(\\u002d\\u002dpadding-section-m) var(\\u002d\\u002dpadding-section-lateral-default)}@media (max-width:767px){.gb-element-4e15e748{padding:var(\\u002d\\u002dpadding-section-m-mobile) var(\\u002d\\u002dpadding-section-lateral-default-mobile)}}\",\"metadata\":{\"name\":\"Section M\",\"categories\":[8]},\"className\":\"wire\"} -->\n<section class=\"gb-element-4e15e748 wire\"><!-- wp:generateblocks/element {\"uniqueId\":\"f65d3d9a\",\"tagName\":\"div\",\"styles\":{\"maxWidth\":\"var(\\u002d\\u002dgb-container-width)\",\"marginLeft\":\"auto\",\"marginRight\":\"auto\"},\"css\":\".gb-element-f65d3d9a{margin-left:auto;margin-right:auto;max-width:var(\\u002d\\u002dgb-container-width)}\",\"metadata\":{\"name\":\"Wrapper\"},\"className\":\"wire\"} -->\n<div class=\"gb-element-f65d3d9a wire\"></div>\n<!-- /wp:generateblocks/element --></section>\n<!-- /wp:generateblocks/element -->\";s:7:\"preview\";s:448:\"<style>.gb-element-4e15e748{padding:var(--padding-section-m) var(--padding-section-lateral-default)}@media (max-width:767px){.gb-element-4e15e748{padding:var(--padding-section-m-mobile) var(--padding-section-lateral-default-mobile)}}</style>\n<section class=\"gb-element-4e15e748 wire\"><style>.gb-element-f65d3d9a{margin-left:auto;margin-right:auto;max-width:var(--gb-container-width)}</style>\n<div class=\"gb-element-f65d3d9a wire\"></div>\n</section>\n\";s:7:\"scripts\";a:0:{}s:6:\"styles\";a:0:{}s:10:\"categories\";a:1:{i:0;i:8;}s:20:\"globalStyleSelectors\";a:0:{}s:8:\"formRefs\";a:0:{}}}');
INSERT INTO `wp_postmeta` VALUES (45,54,'_generateblocks_dynamic_css_version','2.2.1');
INSERT INTO `wp_postmeta` VALUES (46,56,'wp_pattern_sync_status','unsynced');
INSERT INTO `wp_postmeta` VALUES (47,56,'generateblocks_patterns_tree','a:1:{i:0;a:9:{s:2:\"id\";s:10:\"pattern-56\";s:5:\"label\";s:9:\"Section L\";s:7:\"pattern\";s:1462:\"<!-- wp:generateblocks/element {\"uniqueId\":\"4e15e748\",\"tagName\":\"section\",\"styles\":{\"paddingTop\":\"var(\\u002d\\u002dpadding-section-l)\",\"paddingRight\":\"var(\\u002d\\u002dpadding-section-lateral-default)\",\"paddingBottom\":\"var(\\u002d\\u002dpadding-section-l)\",\"paddingLeft\":\"var(\\u002d\\u002dpadding-section-lateral-default)\",\"@media (max-width:767px)\":{\"paddingTop\":\"var(\\u002d\\u002dpadding-section-l-mobile)\",\"paddingRight\":\"var(\\u002d\\u002dpadding-section-lateral-default-mobile)\",\"paddingBottom\":\"var(\\u002d\\u002dpadding-section-l-mobile)\",\"paddingLeft\":\"var(\\u002d\\u002dpadding-section-lateral-default-mobile)\"}},\"css\":\".gb-element-4e15e748{padding:var(\\u002d\\u002dpadding-section-l) var(\\u002d\\u002dpadding-section-lateral-default)}@media (max-width:767px){.gb-element-4e15e748{padding:var(\\u002d\\u002dpadding-section-l-mobile) var(\\u002d\\u002dpadding-section-lateral-default-mobile)}}\",\"metadata\":{\"name\":\"Section L\",\"categories\":[8]},\"className\":\"wire\"} -->\n<section class=\"gb-element-4e15e748 wire\"><!-- wp:generateblocks/element {\"uniqueId\":\"f65d3d9a\",\"tagName\":\"div\",\"styles\":{\"maxWidth\":\"var(\\u002d\\u002dgb-container-width)\",\"marginLeft\":\"auto\",\"marginRight\":\"auto\"},\"css\":\".gb-element-f65d3d9a{margin-left:auto;margin-right:auto;max-width:var(\\u002d\\u002dgb-container-width)}\",\"metadata\":{\"name\":\"Wrapper\"},\"className\":\"wire\"} -->\n<div class=\"gb-element-f65d3d9a wire\"></div>\n<!-- /wp:generateblocks/element --></section>\n<!-- /wp:generateblocks/element -->\";s:7:\"preview\";s:448:\"<style>.gb-element-4e15e748{padding:var(--padding-section-l) var(--padding-section-lateral-default)}@media (max-width:767px){.gb-element-4e15e748{padding:var(--padding-section-l-mobile) var(--padding-section-lateral-default-mobile)}}</style>\n<section class=\"gb-element-4e15e748 wire\"><style>.gb-element-f65d3d9a{margin-left:auto;margin-right:auto;max-width:var(--gb-container-width)}</style>\n<div class=\"gb-element-f65d3d9a wire\"></div>\n</section>\n\";s:7:\"scripts\";a:0:{}s:6:\"styles\";a:0:{}s:10:\"categories\";a:1:{i:0;i:8;}s:20:\"globalStyleSelectors\";a:0:{}s:8:\"formRefs\";a:0:{}}}');
INSERT INTO `wp_postmeta` VALUES (48,56,'_generateblocks_dynamic_css_version','2.2.1');
INSERT INTO `wp_postmeta` VALUES (49,62,'wp_pattern_sync_status','unsynced');
INSERT INTO `wp_postmeta` VALUES (50,62,'generateblocks_patterns_tree','a:1:{i:0;a:9:{s:2:\"id\";s:10:\"pattern-62\";s:5:\"label\";s:10:\"Section XL\";s:7:\"pattern\";s:1469:\"<!-- wp:generateblocks/element {\"uniqueId\":\"4e15e748\",\"tagName\":\"section\",\"styles\":{\"paddingTop\":\"var(\\u002d\\u002dpadding-section-xl)\",\"paddingRight\":\"var(\\u002d\\u002dpadding-section-lateral-default)\",\"paddingBottom\":\"var(\\u002d\\u002dpadding-section-xl)\",\"paddingLeft\":\"var(\\u002d\\u002dpadding-section-lateral-default)\",\"@media (max-width:767px)\":{\"paddingTop\":\"var(\\u002d\\u002dpadding-section-xl-mobile)\",\"paddingRight\":\"var(\\u002d\\u002dpadding-section-lateral-default-mobile)\",\"paddingBottom\":\"var(\\u002d\\u002dpadding-section-xl-mobile)\",\"paddingLeft\":\"var(\\u002d\\u002dpadding-section-lateral-default-mobile)\"}},\"css\":\".gb-element-4e15e748{padding:var(\\u002d\\u002dpadding-section-xl) var(\\u002d\\u002dpadding-section-lateral-default)}@media (max-width:767px){.gb-element-4e15e748{padding:var(\\u002d\\u002dpadding-section-xl-mobile) var(\\u002d\\u002dpadding-section-lateral-default-mobile)}}\",\"metadata\":{\"name\":\"Section XL\",\"categories\":[8]},\"className\":\"wire\"} -->\n<section class=\"gb-element-4e15e748 wire\"><!-- wp:generateblocks/element {\"uniqueId\":\"f65d3d9a\",\"tagName\":\"div\",\"styles\":{\"maxWidth\":\"var(\\u002d\\u002dgb-container-width)\",\"marginLeft\":\"auto\",\"marginRight\":\"auto\"},\"css\":\".gb-element-f65d3d9a{margin-left:auto;margin-right:auto;max-width:var(\\u002d\\u002dgb-container-width)}\",\"metadata\":{\"name\":\"Wrapper\"},\"className\":\"wire\"} -->\n<div class=\"gb-element-f65d3d9a wire\"></div>\n<!-- /wp:generateblocks/element --></section>\n<!-- /wp:generateblocks/element -->\";s:7:\"preview\";s:450:\"<style>.gb-element-4e15e748{padding:var(--padding-section-xl) var(--padding-section-lateral-default)}@media (max-width:767px){.gb-element-4e15e748{padding:var(--padding-section-xl-mobile) var(--padding-section-lateral-default-mobile)}}</style>\n<section class=\"gb-element-4e15e748 wire\"><style>.gb-element-f65d3d9a{margin-left:auto;margin-right:auto;max-width:var(--gb-container-width)}</style>\n<div class=\"gb-element-f65d3d9a wire\"></div>\n</section>\n\";s:7:\"scripts\";a:0:{}s:6:\"styles\";a:0:{}s:10:\"categories\";a:1:{i:0;i:8;}s:20:\"globalStyleSelectors\";a:0:{}s:8:\"formRefs\";a:0:{}}}');
INSERT INTO `wp_postmeta` VALUES (51,62,'_generateblocks_dynamic_css_version','2.2.1');
INSERT INTO `wp_postmeta` VALUES (52,65,'wp_pattern_sync_status','unsynced');
INSERT INTO `wp_postmeta` VALUES (53,65,'generateblocks_patterns_tree','a:1:{i:0;a:9:{s:2:\"id\";s:10:\"pattern-65\";s:5:\"label\";s:11:\"Section XXL\";s:7:\"pattern\";s:1476:\"<!-- wp:generateblocks/element {\"uniqueId\":\"4e15e748\",\"tagName\":\"section\",\"styles\":{\"paddingTop\":\"var(\\u002d\\u002dpadding-section-xxl)\",\"paddingRight\":\"var(\\u002d\\u002dpadding-section-lateral-default)\",\"paddingBottom\":\"var(\\u002d\\u002dpadding-section-xxl)\",\"paddingLeft\":\"var(\\u002d\\u002dpadding-section-lateral-default)\",\"@media (max-width:767px)\":{\"paddingTop\":\"var(\\u002d\\u002dpadding-section-xxl-mobile)\",\"paddingRight\":\"var(\\u002d\\u002dpadding-section-lateral-default-mobile)\",\"paddingBottom\":\"var(\\u002d\\u002dpadding-section-xxl-mobile)\",\"paddingLeft\":\"var(\\u002d\\u002dpadding-section-lateral-default-mobile)\"}},\"css\":\".gb-element-4e15e748{padding:var(\\u002d\\u002dpadding-section-xxl) var(\\u002d\\u002dpadding-section-lateral-default)}@media (max-width:767px){.gb-element-4e15e748{padding:var(\\u002d\\u002dpadding-section-xxl-mobile) var(\\u002d\\u002dpadding-section-lateral-default-mobile)}}\",\"metadata\":{\"name\":\"Section XXL\",\"categories\":[8]},\"className\":\"wire\"} -->\n<section class=\"gb-element-4e15e748 wire\"><!-- wp:generateblocks/element {\"uniqueId\":\"f65d3d9a\",\"tagName\":\"div\",\"styles\":{\"maxWidth\":\"var(\\u002d\\u002dgb-container-width)\",\"marginLeft\":\"auto\",\"marginRight\":\"auto\"},\"css\":\".gb-element-f65d3d9a{margin-left:auto;margin-right:auto;max-width:var(\\u002d\\u002dgb-container-width)}\",\"metadata\":{\"name\":\"Wrapper\"},\"className\":\"wire\"} -->\n<div class=\"gb-element-f65d3d9a wire\"></div>\n<!-- /wp:generateblocks/element --></section>\n<!-- /wp:generateblocks/element -->\";s:7:\"preview\";s:452:\"<style>.gb-element-4e15e748{padding:var(--padding-section-xxl) var(--padding-section-lateral-default)}@media (max-width:767px){.gb-element-4e15e748{padding:var(--padding-section-xxl-mobile) var(--padding-section-lateral-default-mobile)}}</style>\n<section class=\"gb-element-4e15e748 wire\"><style>.gb-element-f65d3d9a{margin-left:auto;margin-right:auto;max-width:var(--gb-container-width)}</style>\n<div class=\"gb-element-f65d3d9a wire\"></div>\n</section>\n\";s:7:\"scripts\";a:0:{}s:6:\"styles\";a:0:{}s:10:\"categories\";a:1:{i:0;i:8;}s:20:\"globalStyleSelectors\";a:0:{}s:8:\"formRefs\";a:0:{}}}');
INSERT INTO `wp_postmeta` VALUES (54,65,'_generateblocks_dynamic_css_version','2.2.1');
/*!40000 ALTER TABLE `wp_postmeta` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_posts`
--

DROP TABLE IF EXISTS `wp_posts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wp_posts` (
  `ID` bigint unsigned NOT NULL AUTO_INCREMENT,
  `post_author` bigint unsigned NOT NULL DEFAULT '0',
  `post_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `post_date_gmt` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `post_content` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `post_title` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `post_excerpt` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `post_status` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'publish',
  `comment_status` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'open',
  `ping_status` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'open',
  `post_password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
  `post_name` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
  `to_ping` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `pinged` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `post_modified` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `post_modified_gmt` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `post_content_filtered` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `post_parent` bigint unsigned NOT NULL DEFAULT '0',
  `guid` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
  `menu_order` int NOT NULL DEFAULT '0',
  `post_type` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'post',
  `post_mime_type` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
  `comment_count` bigint NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`),
  KEY `post_name` (`post_name`(191)),
  KEY `type_status_date` (`post_type`,`post_status`,`post_date`,`ID`),
  KEY `post_parent` (`post_parent`),
  KEY `post_author` (`post_author`),
  KEY `type_status_author` (`post_type`,`post_status`,`post_author`)
) ENGINE=InnoDB AUTO_INCREMENT=107 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_posts`
--

LOCK TABLES `wp_posts` WRITE;
/*!40000 ALTER TABLE `wp_posts` DISABLE KEYS */;
INSERT INTO `wp_posts` VALUES (8,1,'2026-05-14 07:57:05','2026-05-14 07:57:05','<!-- wp:generateblocks/element {\"uniqueId\":\"af2bc7db\",\"tagName\":\"section\",\"styles\":{\"paddingTop\":\"var(\\u002d\\u002dpadding-section-xxs)\",\"paddingRight\":\"var(\\u002d\\u002dpadding-section-lateral-default)\",\"paddingBottom\":\"var(\\u002d\\u002dpadding-section-xxs)\",\"paddingLeft\":\"var(\\u002d\\u002dpadding-section-lateral-default)\",\"@media (max-width:767px)\":{\"paddingTop\":\"var(\\u002d\\u002dpadding-section-xxs-mobile)\",\"paddingRight\":\"var(\\u002d\\u002dpadding-section-lateral-default-mobile)\",\"paddingBottom\":\"var(\\u002d\\u002dpadding-section-xxs-mobile)\",\"paddingLeft\":\"var(\\u002d\\u002dpadding-section-lateral-default-mobile)\"}},\"css\":\".gb-element-af2bc7db{padding:var(\\u002d\\u002dpadding-section-xxs) var(\\u002d\\u002dpadding-section-lateral-default)}@media (max-width:767px){.gb-element-af2bc7db{padding:var(\\u002d\\u002dpadding-section-xxs-mobile) var(\\u002d\\u002dpadding-section-lateral-default-mobile)}}\",\"metadata\":{\"name\":\"Section XXS\",\"categories\":[8]},\"className\":\"wire\"} -->\n<section class=\"gb-element-af2bc7db wire\"><!-- wp:generateblocks/element {\"uniqueId\":\"ebf55113\",\"tagName\":\"div\",\"styles\":{\"maxWidth\":\"var(\\u002d\\u002dgb-container-width)\",\"marginLeft\":\"auto\",\"marginRight\":\"auto\"},\"css\":\".gb-element-ebf55113{margin-left:auto;margin-right:auto;max-width:var(\\u002d\\u002dgb-container-width)}\",\"metadata\":{\"name\":\"Wrapper\"},\"className\":\"wire\"} -->\n<div class=\"gb-element-ebf55113 wire\"><!-- wp:generateblocks/text {\"uniqueId\":\"fca3a059\",\"tagName\":\"h2\"} -->\n<h2 class=\"gb-text\">Section XXS</h2>\n<!-- /wp:generateblocks/text --></div>\n<!-- /wp:generateblocks/element --></section>\n<!-- /wp:generateblocks/element -->\n\n<!-- wp:generateblocks/element {\"uniqueId\":\"1556602d\",\"tagName\":\"section\",\"styles\":{\"paddingTop\":\"var(\\u002d\\u002dpadding-section-xs)\",\"paddingRight\":\"var(\\u002d\\u002dpadding-section-lateral-default)\",\"paddingBottom\":\"var(\\u002d\\u002dpadding-section-xs)\",\"paddingLeft\":\"var(\\u002d\\u002dpadding-section-lateral-default)\",\"@media (max-width:767px)\":{\"paddingTop\":\"var(\\u002d\\u002dpadding-section-xs-mobile)\",\"paddingRight\":\"var(\\u002d\\u002dpadding-section-lateral-default-mobile)\",\"paddingBottom\":\"var(\\u002d\\u002dpadding-section-xs-mobile)\",\"paddingLeft\":\"var(\\u002d\\u002dpadding-section-lateral-default-mobile)\"}},\"css\":\".gb-element-1556602d{padding:var(\\u002d\\u002dpadding-section-xs) var(\\u002d\\u002dpadding-section-lateral-default)}@media (max-width:767px){.gb-element-1556602d{padding:var(\\u002d\\u002dpadding-section-xs-mobile) var(\\u002d\\u002dpadding-section-lateral-default-mobile)}}\",\"metadata\":{\"name\":\"Section XS\",\"categories\":[8]},\"className\":\"wire\"} -->\n<section class=\"gb-element-1556602d wire\"><!-- wp:generateblocks/element {\"uniqueId\":\"4ba06841\",\"tagName\":\"div\",\"styles\":{\"maxWidth\":\"var(\\u002d\\u002dgb-container-width)\",\"marginLeft\":\"auto\",\"marginRight\":\"auto\"},\"css\":\".gb-element-4ba06841{margin-left:auto;margin-right:auto;max-width:var(\\u002d\\u002dgb-container-width)}\",\"metadata\":{\"name\":\"Wrapper\"},\"className\":\"wire\"} -->\n<div class=\"gb-element-4ba06841 wire\"><!-- wp:generateblocks/text {\"uniqueId\":\"e67aa0ac\",\"tagName\":\"h2\"} -->\n<h2 class=\"gb-text\">Section XS</h2>\n<!-- /wp:generateblocks/text --></div>\n<!-- /wp:generateblocks/element --></section>\n<!-- /wp:generateblocks/element -->\n\n<!-- wp:generateblocks/element {\"uniqueId\":\"6f2bcf10\",\"tagName\":\"section\",\"styles\":{\"paddingTop\":\"var(\\u002d\\u002dpadding-section-s)\",\"paddingRight\":\"var(\\u002d\\u002dpadding-section-lateral-default)\",\"paddingBottom\":\"var(\\u002d\\u002dpadding-section-s)\",\"paddingLeft\":\"var(\\u002d\\u002dpadding-section-lateral-default)\",\"@media (max-width:767px)\":{\"paddingTop\":\"var(\\u002d\\u002dpadding-section-s-mobile)\",\"paddingRight\":\"var(\\u002d\\u002dpadding-section-lateral-default-mobile)\",\"paddingBottom\":\"var(\\u002d\\u002dpadding-section-s-mobile)\",\"paddingLeft\":\"var(\\u002d\\u002dpadding-section-lateral-default-mobile)\"}},\"css\":\".gb-element-6f2bcf10{padding:var(\\u002d\\u002dpadding-section-s) var(\\u002d\\u002dpadding-section-lateral-default)}@media (max-width:767px){.gb-element-6f2bcf10{padding:var(\\u002d\\u002dpadding-section-s-mobile) var(\\u002d\\u002dpadding-section-lateral-default-mobile)}}\",\"metadata\":{\"name\":\"Section S\",\"categories\":[8]},\"className\":\"wire\"} -->\n<section class=\"gb-element-6f2bcf10 wire\"><!-- wp:generateblocks/element {\"uniqueId\":\"fdb53eac\",\"tagName\":\"div\",\"styles\":{\"maxWidth\":\"var(\\u002d\\u002dgb-container-width)\",\"marginLeft\":\"auto\",\"marginRight\":\"auto\"},\"css\":\".gb-element-fdb53eac{margin-left:auto;margin-right:auto;max-width:var(\\u002d\\u002dgb-container-width)}\",\"metadata\":{\"name\":\"Wrapper\"},\"className\":\"wire\"} -->\n<div class=\"gb-element-fdb53eac wire\"><!-- wp:heading -->\n<h2 class=\"wp-block-heading\">Section S</h2>\n<!-- /wp:heading --></div>\n<!-- /wp:generateblocks/element --></section>\n<!-- /wp:generateblocks/element -->\n\n<!-- wp:generateblocks/element {\"uniqueId\":\"5d0e8ccc\",\"tagName\":\"section\",\"styles\":{\"paddingTop\":\"var(\\u002d\\u002dpadding-section-m)\",\"paddingRight\":\"var(\\u002d\\u002dpadding-section-lateral-default)\",\"paddingBottom\":\"var(\\u002d\\u002dpadding-section-m)\",\"paddingLeft\":\"var(\\u002d\\u002dpadding-section-lateral-default)\",\"@media (max-width:767px)\":{\"paddingTop\":\"var(\\u002d\\u002dpadding-section-m-mobile)\",\"paddingRight\":\"var(\\u002d\\u002dpadding-section-lateral-default-mobile)\",\"paddingBottom\":\"var(\\u002d\\u002dpadding-section-m-mobile)\",\"paddingLeft\":\"var(\\u002d\\u002dpadding-section-lateral-default-mobile)\"}},\"css\":\".gb-element-5d0e8ccc{padding:var(\\u002d\\u002dpadding-section-m) var(\\u002d\\u002dpadding-section-lateral-default)}@media (max-width:767px){.gb-element-5d0e8ccc{padding:var(\\u002d\\u002dpadding-section-m-mobile) var(\\u002d\\u002dpadding-section-lateral-default-mobile)}}\",\"metadata\":{\"name\":\"Section M\",\"categories\":[8]},\"className\":\"wire\"} -->\n<section class=\"gb-element-5d0e8ccc wire\"><!-- wp:generateblocks/element {\"uniqueId\":\"068b1ba9\",\"tagName\":\"div\",\"styles\":{\"maxWidth\":\"var(\\u002d\\u002dgb-container-width)\",\"marginLeft\":\"auto\",\"marginRight\":\"auto\"},\"css\":\".gb-element-068b1ba9{margin-left:auto;margin-right:auto;max-width:var(\\u002d\\u002dgb-container-width)}\",\"metadata\":{\"name\":\"Wrapper\"},\"className\":\"wire\"} -->\n<div class=\"gb-element-068b1ba9 wire\"><!-- wp:generateblocks/text {\"uniqueId\":\"c575e8b9\",\"tagName\":\"h2\"} -->\n<h2 class=\"gb-text\">Section M</h2>\n<!-- /wp:generateblocks/text --></div>\n<!-- /wp:generateblocks/element --></section>\n<!-- /wp:generateblocks/element -->\n\n<!-- wp:generateblocks/element {\"uniqueId\":\"9cb056c6\",\"tagName\":\"section\",\"styles\":{\"paddingTop\":\"var(\\u002d\\u002dpadding-section-l)\",\"paddingRight\":\"var(\\u002d\\u002dpadding-section-lateral-default)\",\"paddingBottom\":\"var(\\u002d\\u002dpadding-section-l)\",\"paddingLeft\":\"var(\\u002d\\u002dpadding-section-lateral-default)\",\"@media (max-width:767px)\":{\"paddingTop\":\"var(\\u002d\\u002dpadding-section-l-mobile)\",\"paddingRight\":\"var(\\u002d\\u002dpadding-section-lateral-default-mobile)\",\"paddingBottom\":\"var(\\u002d\\u002dpadding-section-l-mobile)\",\"paddingLeft\":\"var(\\u002d\\u002dpadding-section-lateral-default-mobile)\"}},\"css\":\".gb-element-9cb056c6{padding:var(\\u002d\\u002dpadding-section-l) var(\\u002d\\u002dpadding-section-lateral-default)}@media (max-width:767px){.gb-element-9cb056c6{padding:var(\\u002d\\u002dpadding-section-l-mobile) var(\\u002d\\u002dpadding-section-lateral-default-mobile)}}\",\"metadata\":{\"name\":\"Section L\",\"categories\":[8]},\"className\":\"wire\"} -->\n<section class=\"gb-element-9cb056c6 wire\"><!-- wp:generateblocks/element {\"uniqueId\":\"8253b4a1\",\"tagName\":\"div\",\"styles\":{\"maxWidth\":\"var(\\u002d\\u002dgb-container-width)\",\"marginLeft\":\"auto\",\"marginRight\":\"auto\"},\"css\":\".gb-element-8253b4a1{margin-left:auto;margin-right:auto;max-width:var(\\u002d\\u002dgb-container-width)}\",\"metadata\":{\"name\":\"Wrapper\"},\"className\":\"wire\"} -->\n<div class=\"gb-element-8253b4a1 wire\"><!-- wp:generateblocks/text {\"uniqueId\":\"4030fd73\",\"tagName\":\"h2\"} -->\n<h2 class=\"gb-text\">Section L</h2>\n<!-- /wp:generateblocks/text --></div>\n<!-- /wp:generateblocks/element --></section>\n<!-- /wp:generateblocks/element -->\n\n<!-- wp:generateblocks/element {\"uniqueId\":\"a1fdfb63\",\"tagName\":\"section\",\"styles\":{\"paddingTop\":\"var(\\u002d\\u002dpadding-section-xl)\",\"paddingRight\":\"var(\\u002d\\u002dpadding-section-lateral-default)\",\"paddingBottom\":\"var(\\u002d\\u002dpadding-section-xl)\",\"paddingLeft\":\"var(\\u002d\\u002dpadding-section-lateral-default)\",\"@media (max-width:767px)\":{\"paddingTop\":\"var(\\u002d\\u002dpadding-section-xl-mobile)\",\"paddingRight\":\"var(\\u002d\\u002dpadding-section-lateral-default-mobile)\",\"paddingBottom\":\"var(\\u002d\\u002dpadding-section-xl-mobile)\",\"paddingLeft\":\"var(\\u002d\\u002dpadding-section-lateral-default-mobile)\"}},\"css\":\".gb-element-a1fdfb63{padding:var(\\u002d\\u002dpadding-section-xl) var(\\u002d\\u002dpadding-section-lateral-default)}@media (max-width:767px){.gb-element-a1fdfb63{padding:var(\\u002d\\u002dpadding-section-xl-mobile) var(\\u002d\\u002dpadding-section-lateral-default-mobile)}}\",\"metadata\":{\"name\":\"Section XL\",\"categories\":[8]},\"className\":\"wire\"} -->\n<section class=\"gb-element-a1fdfb63 wire\"><!-- wp:generateblocks/element {\"uniqueId\":\"7c8325a1\",\"tagName\":\"div\",\"styles\":{\"maxWidth\":\"var(\\u002d\\u002dgb-container-width)\",\"marginLeft\":\"auto\",\"marginRight\":\"auto\"},\"css\":\".gb-element-7c8325a1{margin-left:auto;margin-right:auto;max-width:var(\\u002d\\u002dgb-container-width)}\",\"metadata\":{\"name\":\"Wrapper\"},\"className\":\"wire\"} -->\n<div class=\"gb-element-7c8325a1 wire\"><!-- wp:generateblocks/text {\"uniqueId\":\"90fb84ae\",\"tagName\":\"h2\"} -->\n<h2 class=\"gb-text\">Section XL</h2>\n<!-- /wp:generateblocks/text --></div>\n<!-- /wp:generateblocks/element --></section>\n<!-- /wp:generateblocks/element -->\n\n<!-- wp:generateblocks/element {\"uniqueId\":\"4fc9c5ec\",\"tagName\":\"section\",\"styles\":{\"paddingTop\":\"var(\\u002d\\u002dpadding-section-xxl)\",\"paddingRight\":\"var(\\u002d\\u002dpadding-section-lateral-default)\",\"paddingBottom\":\"var(\\u002d\\u002dpadding-section-xxl)\",\"paddingLeft\":\"var(\\u002d\\u002dpadding-section-lateral-default)\",\"@media (max-width:767px)\":{\"paddingTop\":\"var(\\u002d\\u002dpadding-section-xxl-mobile)\",\"paddingRight\":\"var(\\u002d\\u002dpadding-section-lateral-default-mobile)\",\"paddingBottom\":\"var(\\u002d\\u002dpadding-section-xxl-mobile)\",\"paddingLeft\":\"var(\\u002d\\u002dpadding-section-lateral-default-mobile)\"}},\"css\":\".gb-element-4fc9c5ec{padding:var(\\u002d\\u002dpadding-section-xxl) var(\\u002d\\u002dpadding-section-lateral-default)}@media (max-width:767px){.gb-element-4fc9c5ec{padding:var(\\u002d\\u002dpadding-section-xxl-mobile) var(\\u002d\\u002dpadding-section-lateral-default-mobile)}}\",\"metadata\":{\"name\":\"Section XXL\",\"categories\":[8]},\"className\":\"wire\"} -->\n<section class=\"gb-element-4fc9c5ec wire\"><!-- wp:generateblocks/element {\"uniqueId\":\"af0eec7b\",\"tagName\":\"div\",\"styles\":{\"maxWidth\":\"var(\\u002d\\u002dgb-container-width)\",\"marginLeft\":\"auto\",\"marginRight\":\"auto\"},\"css\":\".gb-element-af0eec7b{margin-left:auto;margin-right:auto;max-width:var(\\u002d\\u002dgb-container-width)}\",\"metadata\":{\"name\":\"Wrapper\"},\"className\":\"wire\"} -->\n<div class=\"gb-element-af0eec7b wire\"><!-- wp:generateblocks/text {\"uniqueId\":\"04442f23\",\"tagName\":\"h2\"} -->\n<h2 class=\"gb-text\">Section XXL</h2>\n<!-- /wp:generateblocks/text --></div>\n<!-- /wp:generateblocks/element --></section>\n<!-- /wp:generateblocks/element -->','Sections','','publish','closed','closed','','homepage','','','2026-06-04 15:46:44','2026-06-04 15:46:44','',0,'http://llummio-wp-blueprint-workspace.local/?page_id=8',0,'page','',0);
INSERT INTO `wp_posts` VALUES (13,1,'2026-05-14 08:37:48','2026-05-14 08:37:48','<!-- wp:page-list /-->','Navigation','','publish','closed','closed','','navigation','','','2026-05-14 08:37:48','2026-05-14 08:37:48','',0,'http://llummio-wp-blueprint-workspace.local/navigation/',0,'wp_navigation','',0);
INSERT INTO `wp_posts` VALUES (20,1,'2026-05-14 08:51:55','2026-05-14 08:51:50',' ','','','publish','closed','closed','','20','','','2026-05-14 08:51:55','2026-05-14 08:51:55','',0,'http://llummio-wp-blueprint-workspace.local/?p=20',1,'nav_menu_item','',0);
INSERT INTO `wp_posts` VALUES (28,1,'2026-05-14 09:05:26','2026-05-14 09:05:26','<!-- wp:generateblocks/element {\"uniqueId\":\"4e15e748\",\"tagName\":\"section\",\"styles\":{\"paddingTop\":\"var(\\u002d\\u002dpadding-section-s)\",\"paddingRight\":\"var(\\u002d\\u002dpadding-section-lateral-default)\",\"paddingBottom\":\"var(\\u002d\\u002dpadding-section-s)\",\"paddingLeft\":\"var(\\u002d\\u002dpadding-section-lateral-default)\",\"@media (max-width:767px)\":{\"paddingTop\":\"var(\\u002d\\u002dpadding-section-s-mobile)\",\"paddingRight\":\"var(\\u002d\\u002dpadding-section-lateral-default-mobile)\",\"paddingBottom\":\"var(\\u002d\\u002dpadding-section-s-mobile)\",\"paddingLeft\":\"var(\\u002d\\u002dpadding-section-lateral-default-mobile)\"}},\"css\":\".gb-element-4e15e748{padding:var(\\u002d\\u002dpadding-section-s) var(\\u002d\\u002dpadding-section-lateral-default)}@media (max-width:767px){.gb-element-4e15e748{padding:var(\\u002d\\u002dpadding-section-s-mobile) var(\\u002d\\u002dpadding-section-lateral-default-mobile)}}\",\"metadata\":{\"name\":\"Section S\"},\"className\":\"wire\"} -->\n<section class=\"gb-element-4e15e748 wire\"><!-- wp:generateblocks/element {\"uniqueId\":\"f65d3d9a\",\"tagName\":\"div\",\"styles\":{\"maxWidth\":\"var(\\u002d\\u002dgb-container-width)\",\"marginLeft\":\"auto\",\"marginRight\":\"auto\"},\"css\":\".gb-element-f65d3d9a{margin-left:auto;margin-right:auto;max-width:var(\\u002d\\u002dgb-container-width)}\",\"metadata\":{\"name\":\"Wrapper\"},\"className\":\"wire\"} -->\n<div class=\"gb-element-f65d3d9a wire\"></div>\n<!-- /wp:generateblocks/element --></section>\n<!-- /wp:generateblocks/element -->','Section S','','publish','closed','closed','','section-s','','','2026-06-04 14:58:10','2026-06-04 14:58:10','',0,'http://llummio-wp-blueprint-workspace.local/section-s/',0,'wp_block','',0);
INSERT INTO `wp_posts` VALUES (50,1,'2026-06-04 15:01:34','2026-06-04 15:01:34','<!-- wp:generateblocks/element {\"uniqueId\":\"4e15e748\",\"tagName\":\"section\",\"styles\":{\"paddingTop\":\"var(\\u002d\\u002dpadding-section-xs)\",\"paddingRight\":\"var(\\u002d\\u002dpadding-section-lateral-default)\",\"paddingBottom\":\"var(\\u002d\\u002dpadding-section-xs)\",\"paddingLeft\":\"var(\\u002d\\u002dpadding-section-lateral-default)\",\"@media (max-width:767px)\":{\"paddingTop\":\"var(\\u002d\\u002dpadding-section-xs-mobile)\",\"paddingRight\":\"var(\\u002d\\u002dpadding-section-lateral-default-mobile)\",\"paddingBottom\":\"var(\\u002d\\u002dpadding-section-xs-mobile)\",\"paddingLeft\":\"var(\\u002d\\u002dpadding-section-lateral-default-mobile)\"}},\"css\":\".gb-element-4e15e748{padding:var(\\u002d\\u002dpadding-section-xs) var(\\u002d\\u002dpadding-section-lateral-default)}@media (max-width:767px){.gb-element-4e15e748{padding:var(\\u002d\\u002dpadding-section-xs-mobile) var(\\u002d\\u002dpadding-section-lateral-default-mobile)}}\",\"metadata\":{\"name\":\"Section XS\",\"categories\":[8]},\"className\":\"wire\"} -->\n<section class=\"gb-element-4e15e748 wire\"><!-- wp:generateblocks/element {\"uniqueId\":\"f65d3d9a\",\"tagName\":\"div\",\"styles\":{\"maxWidth\":\"var(\\u002d\\u002dgb-container-width)\",\"marginLeft\":\"auto\",\"marginRight\":\"auto\"},\"css\":\".gb-element-f65d3d9a{margin-left:auto;margin-right:auto;max-width:var(\\u002d\\u002dgb-container-width)}\",\"metadata\":{\"name\":\"Wrapper\"},\"className\":\"wire\"} -->\n<div class=\"gb-element-f65d3d9a wire\"></div>\n<!-- /wp:generateblocks/element --></section>\n<!-- /wp:generateblocks/element -->','Section XS','','publish','closed','closed','','section-xs','','','2026-06-04 15:02:04','2026-06-04 15:02:04','',0,'http://llummio-wp-blueprint-workspace.local/section-xs/',0,'wp_block','',0);
INSERT INTO `wp_posts` VALUES (52,1,'2026-06-04 15:02:22','2026-06-04 15:02:22','<!-- wp:generateblocks/element {\"uniqueId\":\"4e15e748\",\"tagName\":\"section\",\"styles\":{\"paddingTop\":\"var(\\u002d\\u002dpadding-section-xxs)\",\"paddingRight\":\"var(\\u002d\\u002dpadding-section-lateral-default)\",\"paddingBottom\":\"var(\\u002d\\u002dpadding-section-xxs)\",\"paddingLeft\":\"var(\\u002d\\u002dpadding-section-lateral-default)\",\"@media (max-width:767px)\":{\"paddingTop\":\"var(\\u002d\\u002dpadding-section-xxs-mobile)\",\"paddingRight\":\"var(\\u002d\\u002dpadding-section-lateral-default-mobile)\",\"paddingBottom\":\"var(\\u002d\\u002dpadding-section-xxs-mobile)\",\"paddingLeft\":\"var(\\u002d\\u002dpadding-section-lateral-default-mobile)\"}},\"css\":\".gb-element-4e15e748{padding:var(\\u002d\\u002dpadding-section-xxs) var(\\u002d\\u002dpadding-section-lateral-default)}@media (max-width:767px){.gb-element-4e15e748{padding:var(\\u002d\\u002dpadding-section-xxs-mobile) var(\\u002d\\u002dpadding-section-lateral-default-mobile)}}\",\"metadata\":{\"name\":\"Section XXS\",\"categories\":[8]},\"className\":\"wire\"} -->\n<section class=\"gb-element-4e15e748 wire\"><!-- wp:generateblocks/element {\"uniqueId\":\"f65d3d9a\",\"tagName\":\"div\",\"styles\":{\"maxWidth\":\"var(\\u002d\\u002dgb-container-width)\",\"marginLeft\":\"auto\",\"marginRight\":\"auto\"},\"css\":\".gb-element-f65d3d9a{margin-left:auto;margin-right:auto;max-width:var(\\u002d\\u002dgb-container-width)}\",\"metadata\":{\"name\":\"Wrapper\"},\"className\":\"wire\"} -->\n<div class=\"gb-element-f65d3d9a wire\"></div>\n<!-- /wp:generateblocks/element --></section>\n<!-- /wp:generateblocks/element -->','Section XXS','','publish','closed','closed','','section-xxs','','','2026-06-04 15:05:35','2026-06-04 15:05:35','',0,'http://llummio-wp-blueprint-workspace.local/section-xxs/',0,'wp_block','',0);
INSERT INTO `wp_posts` VALUES (54,1,'2026-06-04 15:03:04','2026-06-04 15:03:04','<!-- wp:generateblocks/element {\"uniqueId\":\"4e15e748\",\"tagName\":\"section\",\"styles\":{\"paddingTop\":\"var(\\u002d\\u002dpadding-section-m)\",\"paddingRight\":\"var(\\u002d\\u002dpadding-section-lateral-default)\",\"paddingBottom\":\"var(\\u002d\\u002dpadding-section-m)\",\"paddingLeft\":\"var(\\u002d\\u002dpadding-section-lateral-default)\",\"@media (max-width:767px)\":{\"paddingTop\":\"var(\\u002d\\u002dpadding-section-m-mobile)\",\"paddingRight\":\"var(\\u002d\\u002dpadding-section-lateral-default-mobile)\",\"paddingBottom\":\"var(\\u002d\\u002dpadding-section-m-mobile)\",\"paddingLeft\":\"var(\\u002d\\u002dpadding-section-lateral-default-mobile)\"}},\"css\":\".gb-element-4e15e748{padding:var(\\u002d\\u002dpadding-section-m) var(\\u002d\\u002dpadding-section-lateral-default)}@media (max-width:767px){.gb-element-4e15e748{padding:var(\\u002d\\u002dpadding-section-m-mobile) var(\\u002d\\u002dpadding-section-lateral-default-mobile)}}\",\"metadata\":{\"name\":\"Section M\",\"categories\":[8]},\"className\":\"wire\"} -->\n<section class=\"gb-element-4e15e748 wire\"><!-- wp:generateblocks/element {\"uniqueId\":\"f65d3d9a\",\"tagName\":\"div\",\"styles\":{\"maxWidth\":\"var(\\u002d\\u002dgb-container-width)\",\"marginLeft\":\"auto\",\"marginRight\":\"auto\"},\"css\":\".gb-element-f65d3d9a{margin-left:auto;margin-right:auto;max-width:var(\\u002d\\u002dgb-container-width)}\",\"metadata\":{\"name\":\"Wrapper\"},\"className\":\"wire\"} -->\n<div class=\"gb-element-f65d3d9a wire\"></div>\n<!-- /wp:generateblocks/element --></section>\n<!-- /wp:generateblocks/element -->','Section M','','publish','closed','closed','','section-m','','','2026-06-04 15:05:23','2026-06-04 15:05:23','',0,'http://llummio-wp-blueprint-workspace.local/section-m/',0,'wp_block','',0);
INSERT INTO `wp_posts` VALUES (56,1,'2026-06-04 15:04:12','2026-06-04 15:04:12','<!-- wp:generateblocks/element {\"uniqueId\":\"4e15e748\",\"tagName\":\"section\",\"styles\":{\"paddingTop\":\"var(\\u002d\\u002dpadding-section-l)\",\"paddingRight\":\"var(\\u002d\\u002dpadding-section-lateral-default)\",\"paddingBottom\":\"var(\\u002d\\u002dpadding-section-l)\",\"paddingLeft\":\"var(\\u002d\\u002dpadding-section-lateral-default)\",\"@media (max-width:767px)\":{\"paddingTop\":\"var(\\u002d\\u002dpadding-section-l-mobile)\",\"paddingRight\":\"var(\\u002d\\u002dpadding-section-lateral-default-mobile)\",\"paddingBottom\":\"var(\\u002d\\u002dpadding-section-l-mobile)\",\"paddingLeft\":\"var(\\u002d\\u002dpadding-section-lateral-default-mobile)\"}},\"css\":\".gb-element-4e15e748{padding:var(\\u002d\\u002dpadding-section-l) var(\\u002d\\u002dpadding-section-lateral-default)}@media (max-width:767px){.gb-element-4e15e748{padding:var(\\u002d\\u002dpadding-section-l-mobile) var(\\u002d\\u002dpadding-section-lateral-default-mobile)}}\",\"metadata\":{\"name\":\"Section L\",\"categories\":[8]},\"className\":\"wire\"} -->\n<section class=\"gb-element-4e15e748 wire\"><!-- wp:generateblocks/element {\"uniqueId\":\"f65d3d9a\",\"tagName\":\"div\",\"styles\":{\"maxWidth\":\"var(\\u002d\\u002dgb-container-width)\",\"marginLeft\":\"auto\",\"marginRight\":\"auto\"},\"css\":\".gb-element-f65d3d9a{margin-left:auto;margin-right:auto;max-width:var(\\u002d\\u002dgb-container-width)}\",\"metadata\":{\"name\":\"Wrapper\"},\"className\":\"wire\"} -->\n<div class=\"gb-element-f65d3d9a wire\"></div>\n<!-- /wp:generateblocks/element --></section>\n<!-- /wp:generateblocks/element -->','Section L','','publish','closed','closed','','section-l','','','2026-06-04 15:06:03','2026-06-04 15:06:03','',0,'http://llummio-wp-blueprint-workspace.local/section-l/',0,'wp_block','',0);
INSERT INTO `wp_posts` VALUES (62,1,'2026-06-04 15:06:17','2026-06-04 15:06:17','<!-- wp:generateblocks/element {\"uniqueId\":\"4e15e748\",\"tagName\":\"section\",\"styles\":{\"paddingTop\":\"var(\\u002d\\u002dpadding-section-xl)\",\"paddingRight\":\"var(\\u002d\\u002dpadding-section-lateral-default)\",\"paddingBottom\":\"var(\\u002d\\u002dpadding-section-xl)\",\"paddingLeft\":\"var(\\u002d\\u002dpadding-section-lateral-default)\",\"@media (max-width:767px)\":{\"paddingTop\":\"var(\\u002d\\u002dpadding-section-xl-mobile)\",\"paddingRight\":\"var(\\u002d\\u002dpadding-section-lateral-default-mobile)\",\"paddingBottom\":\"var(\\u002d\\u002dpadding-section-xl-mobile)\",\"paddingLeft\":\"var(\\u002d\\u002dpadding-section-lateral-default-mobile)\"}},\"css\":\".gb-element-4e15e748{padding:var(\\u002d\\u002dpadding-section-xl) var(\\u002d\\u002dpadding-section-lateral-default)}@media (max-width:767px){.gb-element-4e15e748{padding:var(\\u002d\\u002dpadding-section-xl-mobile) var(\\u002d\\u002dpadding-section-lateral-default-mobile)}}\",\"metadata\":{\"name\":\"Section XL\",\"categories\":[8]},\"className\":\"wire\"} -->\n<section class=\"gb-element-4e15e748 wire\"><!-- wp:generateblocks/element {\"uniqueId\":\"f65d3d9a\",\"tagName\":\"div\",\"styles\":{\"maxWidth\":\"var(\\u002d\\u002dgb-container-width)\",\"marginLeft\":\"auto\",\"marginRight\":\"auto\"},\"css\":\".gb-element-f65d3d9a{margin-left:auto;margin-right:auto;max-width:var(\\u002d\\u002dgb-container-width)}\",\"metadata\":{\"name\":\"Wrapper\"},\"className\":\"wire\"} -->\n<div class=\"gb-element-f65d3d9a wire\"></div>\n<!-- /wp:generateblocks/element --></section>\n<!-- /wp:generateblocks/element -->','Section XL','','publish','closed','closed','','section-xl','','','2026-06-04 15:07:02','2026-06-04 15:07:02','',0,'http://llummio-wp-blueprint-workspace.local/section-xl/',0,'wp_block','',0);
INSERT INTO `wp_posts` VALUES (65,1,'2026-06-04 15:07:43','2026-06-04 15:07:43','<!-- wp:generateblocks/element {\"uniqueId\":\"4e15e748\",\"tagName\":\"section\",\"styles\":{\"paddingTop\":\"var(\\u002d\\u002dpadding-section-xxl)\",\"paddingRight\":\"var(\\u002d\\u002dpadding-section-lateral-default)\",\"paddingBottom\":\"var(\\u002d\\u002dpadding-section-xxl)\",\"paddingLeft\":\"var(\\u002d\\u002dpadding-section-lateral-default)\",\"@media (max-width:767px)\":{\"paddingTop\":\"var(\\u002d\\u002dpadding-section-xxl-mobile)\",\"paddingRight\":\"var(\\u002d\\u002dpadding-section-lateral-default-mobile)\",\"paddingBottom\":\"var(\\u002d\\u002dpadding-section-xxl-mobile)\",\"paddingLeft\":\"var(\\u002d\\u002dpadding-section-lateral-default-mobile)\"}},\"css\":\".gb-element-4e15e748{padding:var(\\u002d\\u002dpadding-section-xxl) var(\\u002d\\u002dpadding-section-lateral-default)}@media (max-width:767px){.gb-element-4e15e748{padding:var(\\u002d\\u002dpadding-section-xxl-mobile) var(\\u002d\\u002dpadding-section-lateral-default-mobile)}}\",\"metadata\":{\"name\":\"Section XXL\",\"categories\":[8]},\"className\":\"wire\"} -->\n<section class=\"gb-element-4e15e748 wire\"><!-- wp:generateblocks/element {\"uniqueId\":\"f65d3d9a\",\"tagName\":\"div\",\"styles\":{\"maxWidth\":\"var(\\u002d\\u002dgb-container-width)\",\"marginLeft\":\"auto\",\"marginRight\":\"auto\"},\"css\":\".gb-element-f65d3d9a{margin-left:auto;margin-right:auto;max-width:var(\\u002d\\u002dgb-container-width)}\",\"metadata\":{\"name\":\"Wrapper\"},\"className\":\"wire\"} -->\n<div class=\"gb-element-f65d3d9a wire\"></div>\n<!-- /wp:generateblocks/element --></section>\n<!-- /wp:generateblocks/element -->','Section XXL','','publish','closed','closed','','section-xxl','','','2026-06-04 15:08:15','2026-06-04 15:08:15','',0,'http://llummio-wp-blueprint-workspace.local/section-xxl/',0,'wp_block','',0);
/*!40000 ALTER TABLE `wp_posts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_term_relationships`
--

DROP TABLE IF EXISTS `wp_term_relationships`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wp_term_relationships` (
  `object_id` bigint unsigned NOT NULL DEFAULT '0',
  `term_taxonomy_id` bigint unsigned NOT NULL DEFAULT '0',
  `term_order` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`object_id`,`term_taxonomy_id`),
  KEY `term_taxonomy_id` (`term_taxonomy_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_term_relationships`
--

LOCK TABLES `wp_term_relationships` WRITE;
/*!40000 ALTER TABLE `wp_term_relationships` DISABLE KEYS */;
INSERT INTO `wp_term_relationships` VALUES (20,7,0);
INSERT INTO `wp_term_relationships` VALUES (28,2,0);
INSERT INTO `wp_term_relationships` VALUES (28,8,0);
INSERT INTO `wp_term_relationships` VALUES (50,2,0);
INSERT INTO `wp_term_relationships` VALUES (50,8,0);
INSERT INTO `wp_term_relationships` VALUES (52,2,0);
INSERT INTO `wp_term_relationships` VALUES (52,8,0);
INSERT INTO `wp_term_relationships` VALUES (54,2,0);
INSERT INTO `wp_term_relationships` VALUES (54,8,0);
INSERT INTO `wp_term_relationships` VALUES (56,2,0);
INSERT INTO `wp_term_relationships` VALUES (56,8,0);
INSERT INTO `wp_term_relationships` VALUES (62,2,0);
INSERT INTO `wp_term_relationships` VALUES (62,8,0);
INSERT INTO `wp_term_relationships` VALUES (65,2,0);
INSERT INTO `wp_term_relationships` VALUES (65,8,0);
/*!40000 ALTER TABLE `wp_term_relationships` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_term_taxonomy`
--

DROP TABLE IF EXISTS `wp_term_taxonomy`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wp_term_taxonomy` (
  `term_taxonomy_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `term_id` bigint unsigned NOT NULL DEFAULT '0',
  `taxonomy` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
  `description` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `parent` bigint unsigned NOT NULL DEFAULT '0',
  `count` bigint NOT NULL DEFAULT '0',
  PRIMARY KEY (`term_taxonomy_id`),
  UNIQUE KEY `term_id_taxonomy` (`term_id`,`taxonomy`),
  KEY `taxonomy` (`taxonomy`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_term_taxonomy`
--

LOCK TABLES `wp_term_taxonomy` WRITE;
/*!40000 ALTER TABLE `wp_term_taxonomy` DISABLE KEYS */;
INSERT INTO `wp_term_taxonomy` VALUES (1,1,'category','',0,0);
INSERT INTO `wp_term_taxonomy` VALUES (2,2,'gblocks_pattern_collections','',0,7);
INSERT INTO `wp_term_taxonomy` VALUES (7,7,'nav_menu','',0,1);
INSERT INTO `wp_term_taxonomy` VALUES (8,8,'wp_pattern_category','',0,7);
/*!40000 ALTER TABLE `wp_term_taxonomy` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_termmeta`
--

DROP TABLE IF EXISTS `wp_termmeta`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wp_termmeta` (
  `meta_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `term_id` bigint unsigned NOT NULL DEFAULT '0',
  `meta_key` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `meta_value` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`meta_id`),
  KEY `term_id` (`term_id`),
  KEY `meta_key` (`meta_key`(191))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_termmeta`
--

LOCK TABLES `wp_termmeta` WRITE;
/*!40000 ALTER TABLE `wp_termmeta` DISABLE KEYS */;
/*!40000 ALTER TABLE `wp_termmeta` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_terms`
--

DROP TABLE IF EXISTS `wp_terms`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wp_terms` (
  `term_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
  `slug` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
  `term_group` bigint NOT NULL DEFAULT '0',
  PRIMARY KEY (`term_id`),
  KEY `slug` (`slug`(191)),
  KEY `name` (`name`(191))
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_terms`
--

LOCK TABLES `wp_terms` WRITE;
/*!40000 ALTER TABLE `wp_terms` DISABLE KEYS */;
INSERT INTO `wp_terms` VALUES (1,'Uncategorized','uncategorized',0);
INSERT INTO `wp_terms` VALUES (2,'Local','local-patterns',0);
INSERT INTO `wp_terms` VALUES (7,'Main Menu','main-menu',0);
INSERT INTO `wp_terms` VALUES (8,'Sections','sections',0);
/*!40000 ALTER TABLE `wp_terms` ENABLE KEYS */;
UNLOCK TABLES;

--

--

--
-- Table structure for table `wp_usermeta`
--

DROP TABLE IF EXISTS `wp_usermeta`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wp_usermeta` (
  `umeta_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL DEFAULT '0',
  `meta_key` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `meta_value` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`umeta_id`),
  KEY `user_id` (`user_id`),
  KEY `meta_key` (`meta_key`(191))
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_usermeta`
--

LOCK TABLES `wp_usermeta` WRITE;
/*!40000 ALTER TABLE `wp_usermeta` DISABLE KEYS */;
INSERT INTO `wp_usermeta` VALUES (1,1,'nickname','llummio-admin');
INSERT INTO `wp_usermeta` VALUES (2,1,'first_name','');
INSERT INTO `wp_usermeta` VALUES (3,1,'last_name','');
INSERT INTO `wp_usermeta` VALUES (4,1,'description','');
INSERT INTO `wp_usermeta` VALUES (5,1,'rich_editing','true');
INSERT INTO `wp_usermeta` VALUES (6,1,'syntax_highlighting','true');
INSERT INTO `wp_usermeta` VALUES (7,1,'comment_shortcuts','false');
INSERT INTO `wp_usermeta` VALUES (8,1,'admin_color','modern');
INSERT INTO `wp_usermeta` VALUES (9,1,'use_ssl','0');
INSERT INTO `wp_usermeta` VALUES (10,1,'show_admin_bar_front','true');
INSERT INTO `wp_usermeta` VALUES (11,1,'locale','');
INSERT INTO `wp_usermeta` VALUES (12,1,'wp_capabilities','a:1:{s:13:\"administrator\";b:1;}');
INSERT INTO `wp_usermeta` VALUES (13,1,'wp_user_level','10');
INSERT INTO `wp_usermeta` VALUES (14,1,'dismissed_wp_pointers','');
INSERT INTO `wp_usermeta` VALUES (15,1,'show_welcome_panel','1');
INSERT INTO `wp_usermeta` VALUES (18,1,'wp_persisted_preferences','a:4:{s:4:\"core\";a:2:{s:26:\"isComplementaryAreaVisible\";b:1;s:24:\"enableChoosePatternModal\";b:1;}s:14:\"core/edit-post\";a:1:{s:12:\"welcomeGuide\";b:0;}s:9:\"_modified\";s:24:\"2026-05-14T08:08:33.737Z\";s:14:\"core/edit-site\";a:2:{s:12:\"welcomeGuide\";b:0;s:16:\"welcomeGuidePage\";b:0;}}');
INSERT INTO `wp_usermeta` VALUES (20,1,'managenav-menuscolumnshidden','a:5:{i:0;s:11:\"link-target\";i:1;s:11:\"css-classes\";i:2;s:3:\"xfn\";i:3;s:11:\"description\";i:4;s:15:\"title-attribute\";}');
INSERT INTO `wp_usermeta` VALUES (21,1,'metaboxhidden_nav-menus','a:1:{i:0;s:12:\"add-post_tag\";}');
INSERT INTO `wp_usermeta` VALUES (23,1,'wp_user-settings','libraryContent=browse');
INSERT INTO `wp_usermeta` VALUES (24,1,'wp_user-settings-time','1780587168');
INSERT INTO `wp_usermeta` VALUES (25,1,'community-events-location','a:1:{s:2:\"ip\";s:9:\"127.0.0.0\";}');
/*!40000 ALTER TABLE `wp_usermeta` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_users`
--

DROP TABLE IF EXISTS `wp_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wp_users` (
  `ID` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_login` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
  `user_pass` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
  `user_nicename` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
  `user_email` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
  `user_url` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
  `user_registered` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `user_activation_key` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
  `user_status` int NOT NULL DEFAULT '0',
  `display_name` varchar(250) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`ID`),
  KEY `user_login_key` (`user_login`),
  KEY `user_nicename` (`user_nicename`),
  KEY `user_email` (`user_email`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_users`
--

LOCK TABLES `wp_users` WRITE;
/*!40000 ALTER TABLE `wp_users` DISABLE KEYS */;
INSERT INTO `wp_users` VALUES (1,'llummio-admin','264652fac17a35a11f60cd12145715d6','llummio-admin','info@llumm.io','https://llumm.io','2026-05-14 07:41:12','',0,'Llummio Admin');
/*!40000 ALTER TABLE `wp_users` ENABLE KEYS */;
UNLOCK TABLES;

--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-06-04 22:25:52
