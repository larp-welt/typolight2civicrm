-- **********************************************************
-- *                                                        *
-- * IMPORTANT NOTE                                         *
-- *                                                        *
-- * Do not import this file manually but use the TYPOlight *
-- * install tool to create and maintain database tables!   *
-- *                                                        *
-- **********************************************************

-- 
-- Table `tl_news_archive`
-- 

CREATE TABLE `tl_newsletter_channel` (
  `use_civicrm` char(1) NOT NULL default '',
  `civicrm_group` int(16) NULL default NULL,
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE `tl_member` (
  `civicrm_id` int(10) NULL default NULL,
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
