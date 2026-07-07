-- Fix typo domains in ToolsMandi (ahrefs_websites) so go-proxy resolves the correct website_id.
-- Run on ToolsMandi MySQL, then restart affected nx*/tz* proxy services on 1clkaccess.store.
--
-- Example:
--   mysql -u USER -p toolsmandirefct < deploy/fix-toolsmandi-lclkaccess-domains.sql

UPDATE ahrefs_websites
SET domain = REPLACE(domain, '.lclkaccess.store', '.1clkaccess.store')
WHERE domain LIKE '%.lclkaccess.store';
