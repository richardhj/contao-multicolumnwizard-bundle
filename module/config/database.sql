-- **********************************************************
-- *                                                        *
-- * IMPORTANT NOTE                                         *
-- *                                                        *
-- * Do not import this file manually but use the Contao    *
-- * install tool to create and maintain database tables!   *
-- *                                                        *
-- **********************************************************

-- 
-- Table `tl_metamodel_age`
-- 

CREATE TABLE `tl_metamodel_age` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `tstamp` int(10) unsigned NOT NULL default '0',
  `att_id` int(10) unsigned NOT NULL default '0',
  `item_id` int(10) unsigned NOT NULL default '0',
  `lower` int(5) unsigned NOT NULL default '0',
  `upper` int(5) unsigned NOT NULL default '0'
  PRIMARY KEY  (`id`),
  UNIQUE KEY `attitem` (`att_id`, `item_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
