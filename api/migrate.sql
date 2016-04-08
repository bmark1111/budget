ALTER TABLE `transaction` ADD `transaction_repeat_id` INT(11) UNSIGNED NULL DEFAULT NULL AFTER `transaction_date`;
ALTER TABLE `transaction` ADD `vendor_id` INT(11) UNSIGNED NOT NULL AFTER `transaction_repeat_id`;
ALTER TABLE `transaction` ADD INDEX(`vendor_id`);
ALTER TABLE `transaction` CHANGE `bank_account_id` `bank_account_id` INT(11) UNSIGNED NULL;

UPDATE `transaction` SET transaction_repeat_id = 1 WHERE description LIKE '%NETFLIX.COM%' AND category_id = 9 AND TYPE = 'DEBIT';

UPDATE `transaction` SET transaction_repeat_id = 2 WHERE description LIKE '%Cox Communications%' AND `date` <= '2016-03-31' AND category_id = 9 AND TYPE = 'DEBIT';

UPDATE `transaction` SET transaction_repeat_id = 18 WHERE description LIKE '%Cox Communications%' AND `date` > '2016-03-31' AND category_id = 9 AND TYPE = 'DEBIT';

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