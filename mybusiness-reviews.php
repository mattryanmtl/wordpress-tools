<?php /** @noinspection SpellCheckingInspection */
/**
 * This file contains the WordPress code for rendering a Google Reviews badge.
 *
 * Usage:
 *
 * 1. Create a Google API Console account if you have not already done so.
 * 2. Register for the Google Places API.
 * 3. Create an API key.
 * 4. Ensure that the API key can be used from wherever this script will execute
 *    by configuring Key Restrictions.
 * 5. Enter your new Google API key into the last line of this file where it says
 *    'YOUR API KEY HERE'
 * 6. Include this file in your theme or plugin by calling include('google-reviews.php');
 * 7. Call the shortcode using either a query attribute (which will lookup the
 *    Place by name), or by specifying the placeId:
 *
 *    [google-reviews-badge query="Program Fuse"]
 *    [google-reviews-badge placeId="ChIJwaKJP7oZz4kROfVyv4AGTw8"]
 *
 *    Note: lookups using a place id are more specific and reduce the risk of
 *    showing the wrong result.  You can find your place id by using the following
 *    tool from Google:
 *
 *    https://developers.google.com/maps/documentation/javascript/examples/places-placeid-finder
 *
 * @author Program Fuse <info@program-fuse.ca>
 * @copyright Copyright (C) 2018 Program Fuse https://program-fuse.ca
 *
 * Copyright (C) 2017 Program Fuse
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * A wrapper for the Google Places API.
 *
 * In order to use the Google Places API wrapper, you need to provide an
 * API key. You can get an API key from the Google Developer Console. The
 * API key can be passed manually via the "apiKey" configuration option.
 *
 * @see https://console.developers.google.com/
 * @author Program Fuse <info@program-fuse.ca>
 * @copyright Copyright (C) 2017 Program Fuse
 */
class RWC_Google_Places
{
    /**
     * The API key.
     *
     * @var    string
     * @access private
     */
    private $apiKey;
    /**
     * The base URL for querying the places API.
     *
     * @var    string
     * @access private
     */
    private $url = 'https://maps.googleapis.com/maps/api/place';
    /**
     * Create a new Places instance.
     *
     * The options array is completely optional.  However, the Places
     * instance needs to be able to find a Google API key somewhere. You
     * can set the API key directly by passing the "apiKey" option in the
     * configuration array.
     *
     * @param array $options An array of configuration options.
     *
     * @constructor
     */
    public function __construct(array $options = array())
    {
        $this->setOptions($options);
    }

    /**
     * Sets the configuration options.
     *
     * @param array $options An array of configuration options.
     *
     * @return void
     */
    public function setOptions(array $options = array())
    {
        // Mix-in defaults.
        $options = array_merge([
            'apiKey' => null
        ], $options);
        $this->setApiKey($options[ 'apiKey' ]);
    }

    /**
     * Sets the API key passed to setOptions().
     *
     * @param string $apiKey The Google API Key to use for queries.
     *
     * @return void
     */
    private function setApiKey($apiKey)
    {
        $this->apiKey = $apiKey;
    }
    /**
     * Returns the API key used to query the Google Places API.
     *
     * If an API was configured, it will be used. If an API key cannot be
     * found, an Exception will be thrown.
     *
     * @return string Returns the Google API key.
     * @throws Exception if no API is configured.
     */
    private function getApiKey()
    {
        // If a key was configured, use it.
        if ($this->apiKey !== null) {
            return $this->apiKey;
        }
        throw new Exception(
            'No Google API key has been configured.'
        );
    }

    /**
     * Finds a Place given a search string.
     *
     * Finds a Place given a search string. Can be the name of a location,
     * an address, etc. If the query succeeds a PHP stdClass will be
     * returned by converting the Places JSON response to an object. If an
     * error occurs while making the request, an Exception will be thrown.
     *
     * @param string $place The place search string.
     *
     * @return stdClass Returns a PHP object containing matching places.
     * @throws Exception if an error occurs while making the request.
     */
    public function findPlaces($place)
    {
        // Make the request.
        /** @noinspection SpellCheckingInspection */
        $response = wp_remote_get($this->url . sprintf(
            '/textsearch/json?key=%s&query=%s',
            $this->getApiKey(),
            urlencode($place)
        ));
        if (is_wp_error($response)) {
            throw new Exception('An error occurred while querying Google ' .
                'Places for location data: ' .
                $response->get_error_message());
        }
        return json_decode($response[ 'body' ]);
    }

    /**
     * Returns details about a particular Google Place, by placeId.
     *
     * The getDetails() method will return details about a specific location
     * in Google Places, by it's unique placeId. The placeId can be found
     * by first searching for the location by name or by address using
     * findPlaces.
     *
     * @param string $placeId The unique id of the place in Google Places.
     *
     * @return stdClass Returns a stdClass with the query results.
     * @throws Exception if an error occurs while making the request.
     */
    public function getDetails($placeId)
    {
        $response = wp_remote_get($this->url . sprintf(
            '/details/json?key=%s&placeid=%s&',
            $this->getApiKey(),
            urlencode($placeId)
        ));
        if (is_wp_error($response)) {
            throw new Exception('An error occurred while querying Google ' .
                'Places for location details: ' .
                $response->get_error_message());
        }
        return json_decode($response[ 'body' ]);
    }
}
/**
 * A class that provides shortcode handlers for generating Google review badges.
 *
 * @see https://console.developers.google.com/
 * @author Program Fuse <info@program-fuse.ca>
 * @copyright Copyright (C) 2017 Program Fuse
 */
class RWC_Google_Reviews
{
    /**
     * Stores the Google API key used to access the API.
     *
     * @var    string
     * @access private
     */
    private $apiKey = null;

    /**
     * Sets the API key used to access the Google API.
     *
     * @param string $apiKey The Google API key.
     *
     * @return void
     */
    public function setApiKey($apiKey)
    {
        $this->apiKey = $apiKey;
    }
    /**
     * Returns the Google API key.
     *
     * @return string Returns the Google API key.
     */
    public function getApiKey()
    {
        return $this->apiKey;
    }
    /**
     * Creates a new RWC_Google_Reviews instance.
     *
     * @param string $apiKey The Google API key.
     *
     * @constructor
     */
    public function __construct($apiKey)
    {
        $this->setApiKey($apiKey);

        // Register the shortcode
        add_shortcode(
            'google-reviews-badge',
            array( $this, 'googleReviewBadge' )
        );
    }
    /**
     * Returns HTML which will provide Rich Data about a business listed
     * on Google Places.
     *
     * The shortcode accepts the following options.
     *
     * The "apiKey" option can be used to manually specify an API key. If the
     * API Key is omitted, the shortcode handler will check to see if one was
     * specified when creating the RWC_Google_Reviews instance.
     *
     * The "query" option can be used to render a badge based on a Google Places
     * query, based on a place name or an address. If this option is used, a
     * badge will be rendered for all matching locations.
     *
     * The "placeId" option can be used to manually specify the id of the place
     * to be rendered.
     *
     * If the API key was valid and a business is found using the query or
     * placeId specified in the shortcode, the method will return HTML in
     * schema.org LocalBusiness format specifying the required business
     * information and review aggregation data.
     *
     * Example Shortcodes:
     *
     * [google_reviews_badge query="Program Fuse"]
     * [google_reviews_badge placeId="XXXXXXXXX"]
     *
     * @param array $options The shortcode options.
     * @return string Returns HTML with LocalBusiness syntax with review counts.
     */
    public function googleReviewBadge($options)
    {
        try {
            // Merge in shortcode attributes with default options
            $options = array_merge([
                'apiKey' => $this->getApiKey(),
                'query'  => null,
                'placeid' => null,
                'placeId' => null
            ], $options);

            // Apparently shortcode attributes are forced to lowercase
            $options['placeId'] = $options['placeid'];

            // Create an instance of the RWC_Google_Places
            $places = new RWC_Google_Places($options);
            // A query was specified, which may return multiple locations.
            if ($options[ 'query' ] !== null) {
                // Do the query.
                $place = $places->findPlaces($options[ 'query' ]);

                // If the query failed, return a message stating why.
                if (isset($place->error_message)) {
                    return sprintf(
                        "Query failed: %s",
                        $place->error_message
                    );
                }

                // If no results, show message stating it.
                if (count($place->results) == 0) {
                    return sprintf(
                        "No Google Places found matching %s",
                        $options[ 'query' ]
                    );
                }
                // Show badge for each.
                $html = '';
                foreach ($place->results as $result) {
                    $html .= $this->getPlaceBadgeHtml(
                        $result->place_id,
                        $places
                    );
                }
                return $html;
            }
            // A single placeId was specified. Generate and return a badge for
            // only that place.
            if ($options[ 'placeId' ] !== null) {
                return $this->getPlaceBadgeHtml(
                    $options[ 'placeId' ],
                    $places
                );
            }
            // Neither option was specified. Not an error, but nothing to show.
            return 'No query or placeId attributes specified on shortcode.';
        } catch (\Exception $e) {
            // An error occurred while querying the Google API.
            return $e->getMessage();
        }
    }
    /**
     * Renders the badge HTML for specific Google Place.
     *
     * If you explore this method you'll notice that we're taking advantage of
     * the WordPress Transients API to cache HTML. We do this so we
     * don't hammer Google with too many queries.
     *
     * @param string $place The unique id of the place in Google Places.
     * @param \RWC\Google\Places $places The Places API wrapper.
     *
     * @return string Returns an HTML string for the badge.
     */
    private function getPlaceBadgeHtml($placeId, $places)
    {
        // Store the HTML in a transient so we don't need to do this often.
        $transient = 'places_badge_html_' . $placeId;
        $html      = get_transient($transient);
        // If transient has value, use it.
        if ($html !== false) {
            return $html;
        }
        // Get details about this Place from the API
        $places = $places->getDetails($placeId);
        // Make sure a Place was found.
        if (count($places->result) != 1) {
            return sprintf(
                "No Google Places found matching placeId %s",
                $placeId
            );
        }
        // Set the basic details.
        $name = $places->result->name;
        $url = $places->result->website;
        $rating = $places->result->rating;
        $reviewCount = count($places->result->reviews);
        // Use a reduction function to find and set city.
        $city = array_reduce($places->result->address_components, function ($c, $i) {
            if (in_array('locality', $i->types)) {
                $c = $i->long_name;
            }
            return $c;
        });
        // Use a reduction function to find and set state
        $state = array_reduce($places->result->address_components, function ($c, $i) {
            if (in_array('administrative_area_level_1', $i->types)) {
                $c = $i->short_name;
            }
            return $c;
        });
        // Use a reduction function to find and set zipcode
        $zipcode = array_reduce($places->result->address_components, function ($c, $i) {
            if (in_array('postal_code', $i->types)) {
                $c = $i->short_name;
            }
            return $c;
        });
        // Use a reduction function to find and set country
        $country = array_reduce($places->result->address_components, function ($c, $i) {
            if (in_array('country', $i->types)) {
                $c = $i->short_name;
            }
            return $c;
        });
        // Start output buffer and generate HTML.
        ob_start(); ?>
        <div itemscope itemtype="http://schema.org/LocalBusiness">
            <a itemprop="url" href="<?php echo esc_html($url); ?>">
                <div itemprop="name"><?php echo esc_html($name); ?></div>
            </a>
            <div itemprop="address" itemscope itemtype="http://schema.org/PostalAddress">
                <?php if ($pobox !== null) : ?>
                    P.O. Box: <span itemprop="postOfficeBoxNumber"><?php echo esc_html($pobox); ?></span>,
                <?php endif; ?>
                <span itemprop="addressLocality"><?php echo esc_html($city); ?></span>
                <span itemprop="addressRegion"><?php echo esc_html($state); ?></span>
                <span itemprop="postalCode"><?php echo esc_html($zipcode); ?></span>
                <span itemprop="addressCountry"><?php echo esc_html($country); ?></span>
            </div>
            <?php if ($tel !== null) : ?>
                <span class="tel">Tel :
                    <span itemprop="telephone">
                        <a href="tel:<?php echo esc_attr($tel); ?>"><?php echo esc_html($tel); ?></a>
                    </span>
                </span>
            <?php endif; ?>
            <?php if ($fax !== null) : ?>
                <span class="fax">Fax :
                    <span itemprop="faxNumber">555-555-5555</span>
                </span>
            <?php endif; ?>
            <?php if ($email !== null) : ?>
                <span class="email">Email :
                    <span itemprop="email">
                        <a href="mailto:breich@reich-consulting.net">breich@reich-consulting.net</a>
                    </span>
                </span>
            <?php endif; ?>
            <span itemprop="aggregateRating" itemscope itemtype="http://schema.org/AggregateRating">
                Rated <span itemprop="ratingValue">
                    <?php echo esc_html($rating); ?></span>/<span itemprop="bestRating">
                        <?php echo esc_html($maxRating); ?></span>
                based on <span itemprop="reviewCount"><?php echo esc_html($reviewCount); ?></span>
                customer reviews.
            </span>
        </div>
        <?php
        // Grab buffered content
        $html = ob_get_contents();
        // Stop buffering
        ob_end_clean();
        // Save HTML so we don't have to do that mess for a while.
        set_transient($transient, $html, 60 * 60); // One Hour
        // Return it.
        return $html;
    }
}
$reviews = new RWC_Google_Reviews('YOUR API KEY HERE');
