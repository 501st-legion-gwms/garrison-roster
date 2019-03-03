<?php
/**
 * @filename sample.php
 * @author Christopher M. Moody, TK-394
 * @email chrisATtk394DOTcom
 * @version 2.1
 * @date 2019-03-02
 * @time: 22:22 CST
 */

// ************************** Edit the below variables for your site *******************************************

// URL to the Legion's JSON feed.  Change the number to your specific unit.
// ID numbers can be found by going to http://www.501st.com/members/displayUnits.php#garrisons
// hovering your mouse over the 'Roster of Unit Member Costumes' link and looking for
// the number in URL.  Example: In the link for the 70th Explorers Garrison,
// http://www.501st.com/members/garrisonroster.php?garrisonId=57 , the ID number is 57.
//
// $json_url = 'https://www.501st.com/memberAPI/v3/garrisons/<your unit ID here>/members';
$json_url = 'https://www.501st.com/memberAPI/v3/garrisons/57/members';

// FULL PATH to your cache file (Not just the web path, but the FULL path).
// If you can put it BELOW the public web root, that's great!
// Don't forget to CHMOD to make the file writable!  (CHMOD 755 should work, I think.)
$cache_file = "/var/www/cache/legion_api_members.json";

// ************************** Edit the above variables for your site *******************************************


// Read the JSON contents of that page into a variable.
$json = getMembers($json_url, $cache_file);

// Decode the JSON into an object.
$legionData = json_decode($json);

// Sanitize and assign the unit name to an easy to read variable.
$unitName = filter_var($legionData->unit->name, FILTER_SANITIZE_STRING);

// Sanitize and assign the number of members in the unit to an easy to read variable.
$numberMembers = filter_var($legionData->unit->unitSize, FILTER_SANITIZE_NUMBER_INT);

// Assign just the officers to an object: $officers.
$officers = $legionData->unit->officers;

// Assign the members to an object: $members.
$members = $legionData->unit->members;


/**
 * Ugh... The formattedLegionId has '&nbsp;' between the TK and the number.
 * htmlentities then tries to convert the & in &nbsp; resulting in TK&nbsp;394
 * on the screen. That caused me to write this function to counter that.
 *
 * @param string $tk
 * @return string
 */
function html_ent_de_and_recode($tk) {
    $tk = html_entity_decode($tk);
    return htmlentities($tk);
}

/**
 * Function to grab the data from the cache, or if the cache is expired get it from the Legion
 * and then update the cache.
 *
 * @param string $json_url
 * @param string $cache_file
 * @return string
 */
function getMembers($json_url, $cache_file) {
    if (file_exists($cache_file) && (filemtime($cache_file) > (time() - 60 * 60 * 4 ))) {
        // If the cache file is less than 4 hours old,
        // Don't bother refreshing, just use the file as-is.
        $json = file_get_contents($cache_file);
    } else {
        // Our cache is out-of-date, so load the data from our remote server,
        // and also save it over our cache for next time.
        // This shouldn't happen, because crontab gets it every hour.
        $json = file_get_contents($json_url);
        file_put_contents($cache_file, $json);
    }
return $json;
}
?>

<!-- Garrison Officers -->
<div id="unit-officers">
    <h3><?=$unitName?> Officers</h3>
    <ul>
        <? foreach ($officers as $officer): ?>
            <li style="padding-bottom: 10px;"><strong><?=htmlentities($officer->office)?> (<?=htmlentities($officer->officeAcronym)?>):</strong>
            <a href="<?=filter_var($officer->profileUrl, FILTER_SANITIZE_URL)?>"><?=htmlentities($officer->fullName)?> - <?=html_ent_de_and_recode($officer->formattedLegionId)?></a></li>
        <? endforeach; ?>
    </ul>
</div>


<hr>   <!-- Just a line between officers and members.  Delete this if you don't like it. -->


<!-- Garrison Roster -->

<!-- Adjust the "max-width" value of "all-members-wrapper" to control how many members are displayed on each row. -->
<div id="all-members-wrapper" style="max-width: 810px; ">
    <!-- Display the unit name and number of members -->
    <h3><?=$legionData->unit->name?> Roster (<?=$legionData->unit->unitSize?> members):</h3>
    <ul style="list-style-type: none;">
        <!-- Display a thumbnail and TK ID with link to profile, for each member -->
        <? foreach ($members as $member):
            if ($member->fullName == 'Classified information (private)') $member->fullName = 'Classified'; ?>
            <li style="display: inline-block; vertical-align: top;">
                <!-- This is a DIV wrapper around each member. Beware of long names if you shrink the width. -->
                <div id="member-wrapper" style="width: 150px; text-align: center; margin: 0 auto; padding-top: 30px;">
                    <!-- <BR> line breaks will screw up horizontal <LI>s, so we have to use <DIV>s instead. -->
                    <div id="member-tk"><?=html_ent_de_and_recode($member->formattedLegionId)?></div>
                    <div id="member-thumb"><a href="<?=filter_var($member->link, FILTER_SANITIZE_URL)?>"><img src="<?=htmlentities($member->thumbnail)?>" border="0" width="75" height="101" alt="<?=htmlentities($member->fullName)?>"></a></div>
                    <div id="member-name"><?=htmlentities($member->fullName)?></div>
                </div>
            </li>
        <? endforeach; ?>
    </ul>
</div>
