- ALTER TABLE `transaction` ADD `transaction_repeat_id` INT(11) UNSIGNED NULL DEFAULT NULL AFTER `transaction_date`;
- ALTER TABLE `transaction` ADD `vendor_id` INT(11) UNSIGNED NULL DEFAULT NULL AFTER `transaction_repeat_id`;
- ALTER TABLE `transaction` ADD INDEX(`vendor_id`);
- ALTER TABLE `transaction` CHANGE `bank_account_id` `bank_account_id` INT(11) UNSIGNED NULL;

- ALTER TABLE `transaction_split` ADD `vendor_id` INT(11) UNSIGNED NOT NULL AFTER `id`;

- ALTER TABLE `user_session` ADD `roles` VARCHAR(100) NOT NULL AFTER `user_id`;

UPDATE `transaction` SET transaction_repeat_id = 1 WHERE description LIKE '%NETFLIX.COM%' AND category_id = 9 AND TYPE = 'DEBIT';

UPDATE `transaction` SET transaction_repeat_id = 2 WHERE description LIKE '%Cox Communications%' AND `transaction_date` <= '2016-03-31' AND category_id = 9 AND TYPE = 'DEBIT';

UPDATE `transaction` SET transaction_repeat_id = 18 WHERE description LIKE '%Cox Communications%' AND `transaction_date` > '2016-03-31' AND category_id = 9 AND TYPE = 'DEBIT';

UPDATE `transaction` SET transaction_repeat_id = 4 WHERE description LIKE '%SOUTHERN CALIFORNIA EDISON%' AND category_id = 9 AND TYPE = 'DEBIT';

UPDATE `transaction` SET transaction_repeat_id = 5 WHERE description LIKE '%VERIZON WIRELESS%' AND category_id = 9 AND TYPE = 'DEBIT';

UPDATE `transaction` SET transaction_repeat_id = 6 WHERE description LIKE '%Anacapa Apartmen%' AND category_id = 16 AND TYPE = 'DEBIT';

UPDATE `transaction` SET transaction_repeat_id = 7 WHERE description LIKE '%SOUTHERN CALIFORNIA GAS%' AND category_id = 9 AND TYPE = 'DEBIT';

UPDATE `transaction` SET transaction_repeat_id = 8 WHERE description LIKE '%ITUNES.COM/BILL%' AND category_id = 9 AND TYPE = 'DEBIT';

UPDATE `transaction` SET transaction_repeat_id = 9 WHERE description LIKE '%AAA CA MBR RENEWAL%' AND category_id = 7 AND TYPE = 'DEBIT';

UPDATE `transaction` SET transaction_repeat_id = 10 WHERE description LIKE '%COSTCO WHSE%' AND category_id = 4 AND TYPE = 'DEBIT';

UPDATE `transaction` SET transaction_repeat_id = 11 WHERE description LIKE '%ALLY PAYMT%' AND category_id = 7 AND TYPE = 'DEBIT';

UPDATE `transaction` SET transaction_repeat_id = 12 WHERE description LIKE '%CHECK%' AND `amount` > 400 and amount < 999 AND `category_id` = '2' AND `type` = 'CHECK';

UPDATE `transaction` SET transaction_repeat_id = 13 WHERE description LIKE '%BLUE SHIELD OF CA%' AND category_id = 2 AND TYPE = 'DEBIT';

UPDATE `transaction` SET transaction_repeat_id = 14 WHERE description LIKE '%MERCURY%' AND category_id = 7 AND TYPE = 'DEBIT';

UPDATE `transaction` SET transaction_repeat_id = 15 WHERE description LIKE '%STATE OF CA DEPT OF D%' AND `category_id` = '18' AND TYPE = 'DEBIT';

UPDATE `transaction` SET transaction_repeat_id = 16 WHERE description LIKE '%FID BKG SVC LLC%' AND `category_id` = '10' AND `amount` = 500 AND TYPE = 'DEBIT';

UPDATE `transaction` SET transaction_repeat_id = 17 WHERE description LIKE '%MLS TECHNOLOGIES DIRDEP%' AND `category_id` = '13' AND TYPE = 'CREDIT';


// THIS REQUIRES dropping of index category_id
ALTER TABLE `transaction` CHANGE `category_id` `category_id` INT(11) UNSIGNED NULL;
ALTER TABLE `transaction` ADD INDEX(`category_id`);


--SELECT TR.next_due_date, T.next_due_date, T.transaction_date, T.id
--FROM `transaction` T
--LEFT JOIN transaction_repeat TR ON TR.id = T.transaction_repeat_id
--WHERE T.`description` LIKE '%SOUTHERN CALIFORNIA EDISON%' 
--ORDER BY T.`transaction_date` DESC


ALTER TABLE `transaction_upload` ADD `posted_date` DATE NULL DEFAULT NULL AFTER `transaction_date`;
ALTER TABLE `transaction_upload` ADD `stage` ENUM('PENDING','POSTED') NULL DEFAULT NULL AFTER `check_num`,
									ADD `category` VARCHAR(50) NULL DEFAULT NULL AFTER `stage`,
									ADD `card_num` VARCHAR(20) NULL DEFAULT NULL AFTER `category`;


CREATE TABLE `upload_map` (
  `id` int(11) NOT NULL,
  `account_id` int(11) UNSIGNED NOT NULL,
  `offset` tinyint(1) NOT NULL,
  `field` varchar(50) NOT NULL,
  `type` enum('TEXT','DATE','AMOUNT1','AMOUNT2','DEBIT','CREDIT') NOT NULL,
  `is_deleted` tinyint(1) UNSIGNED NOT NULL DEFAULT '0',
  `created_by` int(11) NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_by` int(11) NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `upload_map` (`id`, `account_id`, `offset`, `field`, `type`, `is_deleted`, `created_by`, `created_at`, `updated_by`, `updated_at`) VALUES
(1, 1, 0, 'type', 'TEXT', 0, 1, '2016-10-17 00:00:00', 1, '2016-10-17 22:33:20'),
(2, 1, 1, 'transaction_date', 'DATE', 0, 1, '2016-10-17 00:00:00', 1, '2016-10-17 22:51:19'),
(3, 1, 2, 'description', 'TEXT', 0, 1, '2016-10-17 00:00:00', 1, '2016-10-17 22:34:05'),
(4, 1, 3, 'amount', 'AMOUNT1', 0, 1, '2016-10-17 00:00:00', 1, '2016-10-21 19:13:57'),
(5, 1, 4, 'check_num', 'TEXT', 0, 1, '2016-10-17 00:00:00', 1, '2016-10-17 22:34:34'),
(6, 6, 0, 'type', 'TEXT', 0, 1, '2016-10-17 00:00:00', 1, '2016-10-18 05:33:20'),
(7, 6, 1, 'transaction_date', 'DATE', 0, 1, '2016-10-17 00:00:00', 1, '2016-10-17 22:51:26'),
(8, 6, 2, 'description', 'TEXT', 0, 1, '2016-10-17 00:00:00', 1, '2016-10-18 05:34:05'),
(9, 6, 3, 'amount', 'AMOUNT1', 0, 1, '2016-10-17 00:00:00', 1, '2016-10-21 19:14:02'),
(10, 6, 4, 'check_num', 'TEXT', 0, 1, '2016-10-17 00:00:00', 1, '2016-10-18 05:34:34'),
(11, 7, 0, 'transaction_date', 'DATE', 0, 1, '2016-10-19 00:00:00', 1, '2016-10-19 16:57:59'),
(12, 7, 1, 'post_date', 'DATE', 0, 1, '2016-10-19 00:00:00', 1, '2016-10-19 16:57:54'),
(13, 7, 2, 'description', 'TEXT', 0, 1, '2016-10-19 00:00:00', 1, '2016-10-19 23:54:44'),
(14, 7, 3, 'amount', 'AMOUNT2', 0, 1, '2016-10-19 00:00:00', 1, '2016-10-21 19:14:15'),
(15, 7, 4, 'category', 'TEXT', 0, 1, '2016-10-19 00:00:00', 1, '2016-10-19 16:57:30'),
(16, 8, 0, 'stage', 'TEXT', 0, 1, '2016-10-19 00:00:00', 1, '2016-10-21 19:06:07'),
(17, 8, 1, 'transaction_date', 'DATE', 0, 1, '2016-10-19 00:00:00', 1, '2016-10-19 23:54:44'),
(18, 8, 2, 'post_date', 'DATE', 0, 1, '2016-10-19 00:00:00', 1, '2016-10-19 23:54:44'),
(19, 8, 3, 'card_num', 'TEXT', 0, 1, '2016-10-19 00:00:00', 1, '2016-10-19 23:54:44'),
(20, 8, 4, 'description', 'TEXT', 0, 1, '2016-10-19 00:00:00', 1, '2016-10-19 23:54:44'),
(21, 8, 5, 'category', 'TEXT', 0, 1, '2016-10-19 00:00:00', 1, '2016-10-19 23:54:44'),
(22, 8, 6, 'amount', 'DEBIT', 0, 1, '2016-10-19 00:00:00', 1, '2016-10-19 17:02:08'),
(23, 8, 7, 'amount', 'CREDIT', 0, 1, '2016-10-19 00:00:00', 1, '2016-10-19 17:02:13');

--
-- Indexes for table `upload_map`
--
ALTER TABLE `upload_map`
  ADD PRIMARY KEY (`id`),
  ADD KEY `account_id` (`account_id`),
  ADD KEY `offset` (`offset`);

--
-- AUTO_INCREMENT for table `upload_map`
--
ALTER TABLE `upload_map`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;