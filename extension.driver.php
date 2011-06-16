<?php

	include_once(TOOLKIT . '/class.entrymanager.php');
	
	Class extension_member_replies extends Extension{
		
		public function about(){
			return array('name' => 'Member Replies',
						 'version' => '1.0',
						 'release-date' => '2011-01-01',
						 'author' => array('name' => 'Nick Dunn',
										   'website' => 'http://nick-dunn.co.uk')
				 		);
		}

		public function uninstall() {
			
		}

		public function install() {
			
			try {
				
				Symphony::Database()->query("CREATE TABLE `sym_member_replies` (
				  `entry_id` int(11) default NULL,
				  `member_id` int(11) default NULL,
				  `last_read_entry_id` int(11) default NULL,
				  `subscribed` enum('yes','no') default 'no',
				  KEY `entry_id` (`entry_id`),
				  KEY `member_id` (`member_id`),
				  KEY `last_read_entry_id` (`last_read_entry_id`),
				  KEY `subscribed` (`subscribed`)
				) ENGINE=MyISAM");
				
				Symphony::Database()->query("CREATE TABLE `sym_fields_member_replies` (
				  `id` int(11) unsigned NOT NULL auto_increment,
				  `field_id` int(11) unsigned NOT NULL,
				  `related_sbl_id` int(8) unsigned NOT NULL,
				  PRIMARY KEY (`id`),
				  KEY `field_id` (`field_id`),
				  KEY `related_sbl_id` (`related_sbl_id`)
				) ENGINE=MyISAM");
				
			}
			catch(Exception $e){
				return FALSE;
			}
			
		}
		
	}