<?php

class Group_Buying_Query_Optimization extends Group_Buying_Controller {

	const SCHEMA_VERSION = 1;

	public static function relationship_table() {
		global $wpdb;
		$table = $wpdb->prefix.'gbs_relationships';
		return $table;
	}

	public static function init() {
		add_action( 'init', array( __CLASS__, 'setup_schema' ), 10, 0 );
		add_filter( 'gb_find_by_meta', array( __CLASS__, 'intercept_find_by_meta' ), 10, 3 );
	}

	/**
	 * Create the database tables and triggers for replicating postmeta
	 * relationships in an optimized table
	 */
	public static function setup_schema() {
		if ( get_option('gbs_query_optimization_schema_version', 0) >= self::SCHEMA_VERSION ) {
			return;
		}
		update_option('gbs_query_optimization_schema_version', self::SCHEMA_VERSION);

		global $wpdb;

		// ensure that we're using InnoDB for posts and postmeta for data integrity
		$result = $wpdb->get_row("SHOW TABLE STATUS LIKE '{$wpdb->posts}'");
		if ( $result->Engine != 'InnoDB' ) {
			$wpdb->query("ALTER TABLE {$wpdb->posts} ENGINE InnoDB");
		}
		$result = $wpdb->get_row("SHOW TABLE STATUS LIKE '{$wpdb->postmeta}'");
		if ( $result->Engine != 'InnoDB' ) {
			$wpdb->query("ALTER TABLE {$wpdb->postmeta} ENGINE InnoDB");
		}

		// create our DB table
		$relationship_table = self::relationship_table();
		$wpdb->query("DROP TABLE IF EXISTS $relationship_table");
		$wpdb->query("CREATE TABLE `$relationship_table` (
			`post_id` BIGINT(20) UNSIGNED NOT NULL ,
			`meta_id` BIGINT(20) UNSIGNED NOT NULL ,
			`relationship_id` BIGINT(20) UNSIGNED NOT NULL ,
			`relationship_type` VARCHAR(48) NOT NULL ,
			INDEX `ix_gbs_relationships_id_type` (`relationship_id` ASC, `relationship_type` ASC) ,
			INDEX `fk_gbs_relationships_post_id` (`post_id` ASC) ,
			CONSTRAINT `fk_gbs_relationships_post_id`
				FOREIGN KEY (`post_id` )
				REFERENCES `{$wpdb->posts}` (`ID` )
				ON DELETE CASCADE
				ON UPDATE CASCADE,
			CONSTRAINT `fk_gbs_relationships_meta_id`
				FOREIGN KEY (`meta_id` )
				REFERENCES `{$wpdb->postmeta}` (`meta_id` )
				ON DELETE CASCADE
				ON UPDATE CASCADE
			)
			ENGINE = InnoDB;");

		// Delete any orphaned data in postmeta (i.e., the post no longer exists)
		$wpdb->query("DELETE pm FROM {$wpdb->postmeta} pm LEFT JOIN {$wpdb->posts} wp ON wp.ID = pm.post_id WHERE wp.ID IS NULL");

		// Copy existing data from postmeta to our new table
		$wpdb->query("INSERT INTO $relationship_table (post_id, meta_id, relationship_id, relationship_type)
			SELECT post_id, meta_id, CONVERT(meta_value, UNSIGNED INTEGER), meta_key FROM {$wpdb->postmeta}
			WHERE meta_key LIKE '%_id' AND meta_value REGEXP '^[0-9]+$' AND post_id IN (SELECT ID FROM {$wpdb->posts});
		");

		// Create triggers to synchronize data between postmeta and the relationship table
		$wpdb->query("DROP TRIGGER IF EXISTS {$wpdb->postmeta}_after_insert");
		$wpdb->query("CREATE TRIGGER {$wpdb->postmeta}_after_insert AFTER INSERT ON `{$wpdb->postmeta}`
			FOR EACH ROW
				BEGIN
					IF NEW.meta_key LIKE '%_id' and NEW.meta_value REGEXP '^[0-9]+$' THEN
						INSERT INTO $relationship_table (post_id, meta_id, relationship_id, relationship_type)
						VALUES (NEW.post_id, NEW.meta_id, CONVERT(NEW.meta_value, UNSIGNED INTEGER), NEW.meta_key);
					END IF;
				END");

		$wpdb->query("DROP TRIGGER IF EXISTS {$wpdb->postmeta}_after_update");
		$wpdb->query("CREATE TRIGGER {$wpdb->postmeta}_after_update AFTER UPDATE ON `{$wpdb->postmeta}`
			FOR EACH ROW
				BEGIN
					IF NEW.meta_key LIKE '%_id' and NEW.meta_value REGEXP '^[0-9]+$' THEN
						UPDATE $relationship_table r
						SET post_id = NEW.post_id, relationship_id = CONVERT(NEW.meta_value, UNSIGNED INTEGER), relationship_type = NEW.meta_key
						WHERE r.meta_id = NEW.meta_id;
					END IF;
				END");
	}

	/**
	 * Filter find_by_meta() queries to use our table instead of
	 * postmeta wherever possible
	 *
	 * @param NULL|array $post_ids
	 * @param string $post_type
	 * @param array $meta
	 *
	 * @return array|null
	 */
	public static function intercept_find_by_meta( $post_ids, $post_type, $meta ) {
		if ( $post_ids !== NULL ) {
			return $post_ids; // Something else filtered first. Don't overwrite.
		}

		if ( empty($meta) ) {
			return NULL; // it's not really a find_by_meta
		}

		foreach ( $meta as $key => $value ) {
			if ( substr($key, -3) != '_id' || !is_int($value) ) {
				return NULL; // we don't know what to do with this search
			}
		}

		global $wpdb;
		$relationship_table = self::relationship_table();
		$found = array();
		foreach ( $meta as $key => $value ) {
			$post_ids = $wpdb->get_col($wpdb->prepare("SELECT post_id FROM $relationship_table WHERE relationship_id=%d AND relationship_type=%s", $value, $key));
			if ( empty($post_ids) ) {
				return array(); // no need for further processing
			}
			$found[] = $post_ids;
		}
		if ( count($found) == 1 ) {
			$post_ids = $found[0];
		} else {
			$post_ids = call_user_func_array('array_intersect', $found);
		}
		if ( empty($post_ids) ) {
			return array();
		}

		// we've narrowed down the possible post IDs, but still
		// need to run through get_posts() to allow for proper
		// filtering and whatnot
		$args = array(
			'post_type' => $post_type,
			'post_status' => 'any',
			'posts_per_page' => -1,
			'fields' => 'ids',
			'gb_bypass_filter' => TRUE,
			'post__in' => $post_ids,
		);

		$result = get_posts($args);
		return $result;
	}

}
