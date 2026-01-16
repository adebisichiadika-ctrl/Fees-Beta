-- Migration script to add payment_date and receipt_file columns to payments table
-- Run this script if you have an existing database

-- Add payment_date column if it doesn't exist
ALTER TABLE `payments` 
ADD COLUMN IF NOT EXISTS `payment_date` date DEFAULT NULL AFTER `remarks`;

-- Add receipt_file column if it doesn't exist
ALTER TABLE `payments` 
ADD COLUMN IF NOT EXISTS `receipt_file` varchar(255) DEFAULT NULL AFTER `payment_date`;

-- Update existing records to set payment_date from date_created if null
UPDATE `payments` SET `payment_date` = DATE(`date_created`) WHERE `payment_date` IS NULL;
