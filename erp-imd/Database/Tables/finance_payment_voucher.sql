-- ISA ANSHORI, 15 Desember 2015, ADD Column deleted
ALTER TABLE imderpdb.finance_payment_voucher 
ADD COLUMN `deleted` tinyint(1) DEFAULT '0';

ALTER TABLE `erpdb`.`finance_payment_voucher` 
ADD COLUMN `deletion` VARCHAR(1000) DEFAULT '';