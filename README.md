# Magento-AttributeCleaner
This Magento1 module deletes unused product attributes from the DB.

The module defaults to "active and scheduled" (works out-of-the-box) but in "dry run mode":

1. Run it as `n98-magerun.phar sys:cron:run boraso_attributecleaner`
1. Check the dryrun log in `magento/var/log/boraso_attributecleaner.log`
1. Disable dryrun in `Admin -> System -> Configuration -> Attribute Cleaner`

