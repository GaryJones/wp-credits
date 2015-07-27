<?php

function gmj_get_versions() {
	return array( '3.2', '3.3', '3.4', '3.5', '3.6', '3.7', '3.8', '3.9', '4.0', '4.1', '4.2' );
}

function gmj_get_credits() {
	static $credits;

	if ( $credits )
		return $credits;

	foreach ( gmj_get_versions() as $version ) {
		$json = file_get_contents( 'http://api.wordpress.org/core/credits/1.1/?locale=en_GB&version=' . $version );
		$credits[$version] = json_decode( $json );
	}

	return $credits;
}

/**
 * Get an array of every user mentioned at least once.
 *
 * @return array All contributors.
 */
function gmj_get_users() {
	//static $users;
	//if ( $users )
	//	return $users;

	$credits = gmj_get_credits();

	foreach( $credits as $version => $version_data ) {

		foreach( $version_data->groups as $group => $group_data ) {
			if ( 'libraries' === $group )
				continue;
			foreach( $group_data->data as $user_name => $user_data ) {
				if ( isset( $users[$user_name]['credits'][$version] ) )
					continue;
				if ( is_string( $user_data ) ) {
					$users[$user_name]['name'] = $user_data;
					$users[$user_name]['credits'][$version] = 'Core Contributor';
				} else {
					$users[$user_name]['name'] = $user_data[0];
					$users[$user_name]['credits'][$version] = 'Unknown Role'; // Fallback
					if ( isset( $user_data[3] ) && $user_data[3] )
						$users[$user_name]['credits'][$version] = $user_data[3];
					elseif ( $group_data->name )
						$users[$user_name]['credits'][$version] = $group_data->name;
				}
			}
		}
	}

	uasort( $users, 'gmj_sort_by_name' );
	return $users;
}

function gmj_sort_by_name( $a, $b ) {
    return strcasecmp( $a['name'], $b['name'] );
}

function gmj_do_table() {
	$users = gmj_get_users();
	echo '<table class="table table-bordered table-striped table-hover"><caption>All ' . count( $users ) . ' people credited for contributing to WordPress (there may be a few duplicates).</caption><thead><tr><th>Name</th>';
	foreach ( gmj_get_versions() as $version )
		echo '<th>' . $version . '</th>';
	echo '</thead><tbody>';
	foreach( $users as $profile_name => $user_data ) {
		echo '<tr><th><a href="http://profiles.wordpress.org/' . $profile_name . '">' . $user_data['name'] . '</a></th>';
		foreach ( gmj_get_versions() as $version ) {
			echo '<td>';
			if ( isset( $user_data['credits'][$version] ) ) {
				echo $user_data['credits'][$version];
			}
			echo '</td>';
		}
		echo '</tr>';
	}
	echo '</tbody></table>';
}

?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" lang="en-GB" xml:lang="en-GB">
	<head>
		<meta charset="UTF-8"/>
		<title>All Credited WordPress Contributors</title>
		<link rel="stylesheet" type="text/css" href="bootstrap.css"/>
	</head>
	<body>
		<div class="container">
			<h1>All Credited WordPress Contributors <small>(beta)</small></h1>
			<p>WordPress has had an API for listing the names of all the contributors and more in-depth roles since version 3.2. The table below looks at each version, and notes what role someone had, if any, for all of the versions since.</p>
			<p>This is considered beta, as it has no caching of results (currently a standalone page as a proof-of-concept) so it hits the Credits API once for each version, on every page load), and could do with some plural titles tidied up.</p>
			<?php gmj_do_table(); ?>
		</div>
	</body>
</html>
