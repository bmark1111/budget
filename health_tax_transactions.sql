
SELECT T.`transaction_date`, V.name, T.`description`, T.`notes`, T.`type`, T.`amount`
FROM `transaction` T
LEFT JOIN vendor V on T.vendor_id = V.id
WHERE T.`transaction_date` >= '2017-01-01' AND T.`category_id` = 2 AND T.`is_deleted` = 0


SELECT T.`transaction_date`, V.name, T.`description`, TS.`notes`, TS.`type`, TS.`amount`
FROM `transaction` T
LEFT JOIN transaction_split TS ON TS.transaction_id = T.id AND TS.is_deleted=0 AND TS.`category_id` = 2
LEFT JOIN vendor V on TS.vendor_id = V.id
WHERE T.`transaction_date` >= '2017-01-01' AND T.`is_deleted` = 0 AND T.category_id is null and T.vendor_id is null