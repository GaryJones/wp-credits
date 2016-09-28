<?php
/**
 * WordPress Credited Contributors.
 */

/**
 * Versions that are checked,
 *
 * @return array List of version numbers.
 */
function get_versions() {
	return array( '3.2', '3.3', '3.4', '3.5', '3.6', '3.7', '3.8', '3.9', '4.0', '4.1', '4.2', '4.3', '4.4', '4.5', '4.6' );
}

/**
 * Get credits from all versions.
 *
 * @return array Credits.
 */
function get_credits() {
	static $credits;

	if ( $credits ) {
		return $credits;
	}

	foreach ( get_versions() as $version ) {
		$json = file_get_contents( 'http://api.wordpress.org/core/credits/1.1/?version=' . $version );
		$credits[$version] = json_decode( $json );
	}

	return $credits;
}

/**
 * Get an array of every user mentioned at least once.
 *
 * @return array All contributors.
 */
function get_users() {
	$credits = get_credits();

	foreach ( $credits as $version => $version_data ) {
		foreach ( $version_data->groups as $group => $group_data ) {
			// Ignore the credited libraries - we're only interested in people.
			if ( 'libraries' === $group ) {
				continue;
			}
			foreach ( $group_data->data as $user_name => $user_data ) {
				// Set as empty string, to save an isset() later.
				$users[$user_name]['credits'][$version] = '';

				// // Skip over any duplicates
				// if ( ! empty( $users[$user_name]['credits'][$version] ) ) {
				// 	// continue;
				// }

				// Deal with the masses first.
				if ( is_string( $user_data ) ) {
					$users[$user_name]['name'] = $user_data;
					$users[$user_name]['credits'][$version] = 'Core Contributor';
				} else {
					$users[$user_name]['name'] = $user_data[0];
					if ( isset( $user_data[3] ) && $user_data[3] ) {
						// They have a personal title.
						$users[$user_name]['credits'][$version] = $user_data[3];
					} elseif ( 'contributing-developers' === $group ) {
						// They are in a group that has no name.
						$users[$user_name]['credits'][$version] = 'Contributing Developer';
					} elseif ( 'recent-rockstars' === $group ) {
						// They are in a group that has no name.
						$users[$user_name]['credits'][$version] = 'Recent Rockstar';
					} elseif ( isset( $group_data->name ) && $group_data->name ) {
						// They are in a group that has no name that I've not seen before.
						$users[$user_name]['credits'][$version] = $group_data->name;
					} else {
						// I give up.
						$users[$user_name]['credits'][$version] = 'Unknown Role';
					}

					// Tidy plurals from inconsistent data in. i.e. boonebgorges for 4.1.
					if ( 'Contributing Developers' === $users[$user_name]['credits'][$version] ) {
						$users[$user_name]['credits'][$version] = 'Contributing Developer';
					}
				}
			}
		}
	}

	uasort( $users, function( $a, $b ) {
		return strcasecmp( $a['name'], $b['name'] );
	});

	return $users;
}

/**
 * Displat contributors table.
 */
function do_table() {
	$users = get_users();
	?>
	<div class="table-responsive">
		<table class="table table-bordered table-striped table-hover table-condensed">
			<caption>All <?php echo count( $users ); ?> people credited for contributing to WordPress (with around 7 duplicates).</caption>
			<thead>
				<tr>
					<th scope="col">Name</th>
					<?php
					array_walk( get_versions(), function( $version ) {
						?>
						<th scope="col"><?php echo $version; ?></th>
						<?php
					});
					?>
				</tr>
			</thead>
			<tbody>
			<?php
			foreach ( $users as $profile_name => $user_data ) {
				?>
				<tr>
					<th scope="row"><a href="http://profiles.wordpress.org/<?php echo $profile_name; ?>"><?php echo $user_data['name']; ?></a></th>
					<?php
					array_walk(get_versions(), function ( $version ) use ( $user_data ) {
						?>
						<td><?php echo $user_data['credits'][$version]; ?></td>
						<?php
					} );
					?>
				</tr>
				<?php
				}
				?>
			</tbody>
		</table>
		</div>
	<?php
}

?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" lang="en-GB" xml:lang="en-GB">
	<head>
		<meta charset="UTF-8"/>
		<title>All Credited WordPress Contributors</title>
		<link rel="stylesheet" type="text/css" href="bootstrap.min.css" />
		<link rel="stylesheet" type="text/css" href="bootstrap-theme.min.css" />
		<meta name="viewport" content="width=device-width, initial-scale=1" />
	</head>
	<body>
		<div class="container-fluid" style="margin: 1em;">
			<h1>All Credited WordPress Contributors <small>(beta)</small></h1>
			<p class="lead">WordPress has had an API for listing the names of all the contributors and more in-depth roles since version 3.2. The table below looks at each version, and notes what role someone had, if any, for all of the versions since.</p>
			<p><small>This is considered beta, as it has no caching of results (currently a standalone page as a proof-of-concept) so it hits the Credits API once for each version, on every page load).</small></p>
			<?php do_table(); ?>
		</div>
	</body>
</html>
