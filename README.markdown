# Member Replies

* Version: 0.1
* Build Date: 2011-06-16
* Author: Nick Dunn
* Requirements: Symphony 2.2.2

This field works with the Members extension and tracks which entries members have read. More specifically, it assumes a parent/child relationship such as Articles/Comments or Forum Threads/Replies and it will monitor how many replies each member has read. With this field you can build a rudimentary forum system.

XML output:

	<replies has-read-before="no" total-replies="0" unread-replies="0" latest-reply-id="123" latest-reply-date="2011-06-16" latest-reply-time="18:02" />

* `has-read-before` if the user has previously viewed this thread. If they have, and there are unread replies you will want to write "X unread" into your view
* `total-replies` is the total number of child entries (comments, replies etc)
* `unread-replies` is the number of these child entries that the member has not read
* `latest-reply-id` is the ID of the latest child entry (also included as an output parameter)
* `latest-reply-date` and `latest-reply-time` is the date of the latest child entry, for "time ago" processing

The field also provides a "mark as read" data source output mode. Choose this option on the "detail" view of your parent entry (e.g. the page that displays the full discussion thread). This does the action of marking the latest reply as "read" for this logged-in member.

## Installation

1. Upload the 'member_replies' folder in this archive to your Symphony 'extensions' folder.
2. Enable it by selecting the "Member Replies", choose Enable from the with-selected menu, then click Apply.
3. Add a Member Replies field to your discussion "parent" section (e.g. to your Articles section, if a Comments section contains replies)
4. Select the Selectbox Link field in the "child" section (e.g. an "Article" field in the Comments section) when configuring the field