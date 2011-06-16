<?php

	if(!defined('__IN_SYMPHONY__')) die('<h2>Symphony Error</h2><p>You cannot directly access this file</p>');

	Class fieldMember_Replies extends Field{

	/*-------------------------------------------------------------------------
		Definition:
	-------------------------------------------------------------------------*/

		public function __construct(&$parent){
			parent::__construct($parent);
			$this->_name = __('Member Replies');
		}
		
		public function isSortable(){
			return TRUE;
		}

	/*-------------------------------------------------------------------------
		Setup:
	-------------------------------------------------------------------------*/

		public function createTable(){
			return Symphony::Database()->query(
				"CREATE TABLE IF NOT EXISTS `tbl_entries_data_" . $this->get('id') . "` (
					`id` int(11) unsigned NOT NULL auto_increment,
					`entry_id` int(11) unsigned NOT NULL,
					`value` varchar(255) DEFAULT NULL,
					PRIMARY KEY (`id`),
					KEY `entry_id` (`entry_id`)
				) ENGINE=MyISAM;"
			);
		}


	/*-------------------------------------------------------------------------
		Settings:
	-------------------------------------------------------------------------*/

		public function displaySettingsPanel(&$wrapper, $errors=NULL){
			parent::displaySettingsPanel($wrapper, $errors);
			
			$sectionManager = new SectionManager($this->_engine);
			$sections = $sectionManager->fetch(NULL, 'ASC', 'sortorder');
			
			$options = array();
			
			// iterate over sections to build list of fields
			if(is_array($sections) && !empty($sections)) foreach($sections as $section){
				
				$section_fields = $section->fetchFields();
				if(!is_array($section_fields)) continue;

				$fields = array();
				
				foreach($section_fields as $f){
					// only show select box link fields
					if($f->get('type') == 'selectbox_link') {
						$fields[] = array($f->get('id'), ($this->get('related_sbl_id') == $f->get('id')), $f->get('label'));
					}
				}

				if(!empty($fields)) {
					$options[] = array(
						'label' => $section->get('name'),
						'options' => $fields
					);
				}
			}

			$label = Widget::Label(__('Child Select Box Link'));
			$label->appendChild(
				Widget::Select('fields['.$this->get('sortorder').'][related_sbl_id]', $options)
			);

			if(isset($errors['related_sbl_id'])) {
				$wrapper->appendChild(Widget::wrapFormElementWithError($label, $errors['related_sbl_id']));
			}
			else {
				$wrapper->appendChild($label);
			}

		}

		public function checkFields(&$errors, $checkForDuplicates = TRUE) {
			parent::checkFields($errors, $checkForDuplicates);

			$related_fields = $this->get('related_sbl_id');
			if(empty($related_fields)){
				$errors['related_sbl_id'] = __('This is a required field.');
			}

			return (is_array($errors) && !empty($errors) ? self::__ERROR__ : self::__OK__);
		}

		public function commit(){
			if(!parent::commit()) return FALSE;

			$id = $this->get('id');
			if($id === FALSE) return FALSE;
			
			$fields = array();
			$fields['field_id'] = $id;
			$fields['related_sbl_id'] = $this->get('related_sbl_id');
			
			Symphony::Database()->query("DELETE FROM `tbl_fields_".$this->handle()."` WHERE `field_id` = '$id'");
			if(!Symphony::Database()->insert($fields, 'tbl_fields_' . $this->handle())) return FALSE;
			
			return TRUE;
		}

	/*-------------------------------------------------------------------------
		Publish:
	-------------------------------------------------------------------------*/
	
		// always just store an empty (NULL) value
		public function processRawFieldData($data, &$status, $simulate=FALSE, $entry_id=NULL){
			$status = self::__OK__;
			return array(
				'value' => '',
			);
		}

	/*-------------------------------------------------------------------------
		Output:
	-------------------------------------------------------------------------*/
	
		public function fetchIncludableElements() {
			return array(
				$this->get('element_name'),
				$this->get('element_name') . ': mark as read'
			);
		}
		
		// @todo: replace `1` in there queries with a call to Members to get member ID
		// @todo: allow another mode to mark all children as read
		public function appendFormattedElement(&$wrapper, $data, $encode=FALSE, $mode=NULL, $entry_id=NULL){
			
			$element = new XMLElement($this->get('element_name'), NULL);
			
			// for this parent entry, find the ID of the last-read child for this user
			$last_read_entry_id = Symphony::Database()->fetchVar('last_read_entry_id', 0,
				sprintf("SELECT `last_read_entry_id` FROM sym_member_replies WHERE member_id=%d AND entry_id=%d LIMIT 1", 1, $entry_id)
			);
			
			// user has previously read this thread, it's not new, so UI should show unread count
			if(is_null($last_read_entry_id)) {
				$last_read_entry_id = 0;
			}
						
			$child_entries = Symphony::Database()->fetchCol('entry_id', 
				sprintf(
					"SELECT entry_id FROM sym_entries_data_%d WHERE relation_id=%d ORDER BY entry_id ASC",
					$this->get('related_sbl_id'),
					$entry_id
				)
			);
			
			$unread_entries = array();
			foreach($child_entries as $id) {
				if($id > $last_read_entry_id) $unread_entries[] = $id;
			}
			
			$unread_count = count($unread_entries);
			
			$element->setAttribute('new', ($last_read_entry_id > 0) ? 'no' : 'yes');
			$element->setAttribute('total-replies', count($child_entries));
			$element->setAttribute('unread-replies', $unread_count);
			$wrapper->appendChild($element);
			
			if($mode == 'mark as read') {
				
				// find the last child entry ID that exists
				if(count($child_entries) > 0) {
					$last_read_entry_id = end($child_entries);
				}
				// if no children, set the last-ready to be the parent entry ID itself
				else {
					$last_read_entry_id = $entry_id;
				}
				// remove any read state for this parent entry
				Symphony::Database()->query(sprintf("DELETE FROM sym_member_replies WHERE member_id=%d AND entry_id=%d", 1, $entry_id));
				// mark the last child as read
				Symphony::Database()->query(sprintf("INSERT INTO sym_member_replies (member_id, entry_id, last_read_entry_id) VALUES(%d,%d,%d)", 1, $entry_id, $last_read_entry_id));
			}
		}

	/*-------------------------------------------------------------------------
		Sorting:
	-------------------------------------------------------------------------*/

		public function buildSortingSQL(&$joins, &$where, &$sort, $order='ASC'){
			// join on the related SBL field
			$joins .= "LEFT JOIN `tbl_entries_data_".$this->get('related_sbl_id')."` AS `sbl` ON (`e`.`id` = `sbl`.`relation_id`) ";
			// sort by the entry ID, newer entry IDs are higher, so newer rows in the SBL data table indicate newest comments
			$sort = "GROUP BY `e`.`id` ORDER BY (
					CASE WHEN MAX(`sbl`.`entry_id`) IS NULL THEN
						`e`.`id`
					ELSE
						MAX(`sbl`.`entry_id`)
					END	
				) $order";
		}

	}
