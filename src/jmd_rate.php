<?php

/**
 * @name            jmd_rate
 * @description     CSS star rater
 * @author          Jon-Michael Deldin
 * @author_uri      http://jmdeldin.com
 * @version         0.4-DEV
 * @type            1
 * @order           5
 */

//--------------------------------------
// admin

if (@txpinterface == 'admin')
{
	add_privs('jmd_rate_prefs', '1');
	register_tab('extensions', 'jmd_rate_prefs', 'jmd_rate');
	register_callback('jmd_rate_prefs', 'jmd_rate_prefs');
}

function jmd_rate_prefs($event, $step)
{
	ob_start('jmd_rate_prefs_head');
	pagetop('jmd_rate_prefs');
	echo '<div id="jmd_rate_prefs">';

	if (!$step)
	{
		echo fieldset(
			form(
				(
					fInput('submit', 'install', 'Install', 'publish').
					eInput('jmd_rate_prefs').
					sInput('install')
				)
			).
			form(
				(
					fInput('submit', 'uninstall', 'Uninstall', 'publish').
					eInput('jmd_rate_prefs').
					sInput('uninstall')
				), '', "verify('Are you sure you want to delete all ratings?');"
			), 'Setup', 'setup'
		);
		echo fieldset(
			form(
				'<label>Quantity '.fInput('text', 'qty', 4).'</label><br/>
				<label>Path and filename of star image '.fInput('text', 'path', '/stars.png').'</label><br/>
				<label>Star width'.fInput('text', 'width', 19).'</label><br/>
				<label>Star height'.fInput('text', 'height', 18).'</label><br/>
				<label>Container class name'.fInput('text', 'class', 'rating').'</label><br/>'.
				fInput('submit', 'generate', 'Generate CSS', 'publish').
				eInput('jmd_rate_prefs').
				sInput('builder')
			), 'CSS builder'
		);
	}
	elseif ($step == 'install')
	{
		$sql = "CREATE TABLE ".safe_pfx('jmd_rate')."(
			parentid INT,
			value INT,
			max_value INT,
			ip INT UNSIGNED,
			PRIMARY KEY(parentid, ip)
		)";
		$create = safe_query($sql);
		if ($create)
		{
			echo tag('Table created successfully. '.eLink('jmd_rate_prefs', '', '', '', 'Back to preferences?'), 'p', ' class="ok"');
		}
		else
		{
			echo tag('Database exists. '.eLink('jmd_rate_prefs', '', '', '', 'Back to preferences?'), 'p', ' class="not-ok"');
		}
	}
	elseif ($step == 'uninstall')
	{
		safe_query("DROP TABLE IF EXISTS ".safe_pfx('jmd_rate'));
		echo tag('Table dropped. '.eLink('jmd_rate_prefs', '', '', '', 'Back to preferences?'), 'p', ' class="ok"');
	}
	elseif ($step == 'builder')
	{
		if (is_numeric(gps('qty')) && is_numeric(gps('width')) && is_numeric(gps('height')))
		{
			$qty = gps('qty');
			$w = round(gps('width'));
			$h = round(gps('height'));
			$path = htmlentities(gps('path'));
			$class = '.'.gps('class');

			echo tag('CSS', 'h1');
			echo "
<textarea class=\"code\" cols=\"78\" rows=\"32\" id=\"jmd_rate_css\">
$class {}
	$class, $class * {
		margin: 0;
		border: 0;
		padding: 0;
	}
	$class ul {
		height: ".$h."px;
		position: relative;
	}
		$class ul, $class .current_rating, $class a:hover {
			background: url($path);
		}
		$class li {
			list-style: none;
			text-indent: -9999px;
		}
			$class .current_rating {
				background-position: 0 -".$h."px;
				z-index: 1;
			}
				$class .current_rating, $class a {
					height: ".$h."px;
					position: absolute;
					top: 0;
					left: 0;
				}
			$class a {
				width: ".$w."px;
				height: ".$h."px;
				overflow: hidden;
				z-index: 3;
			}
				$class a:hover{
					background-position: left center;
					left: 0;
					z-index: 2;
				}
					".$class."_1 a:hover { width: ".$w."px }
			";
			for ($i = 2; $i <= $qty; $i++)
			{
				echo '
					'.$class.'_'.$i.' a { left: '.($i - 1) * $w.'px }
					'.$class.'_'.$i.' a:hover { width: '.$w * $i.'px }
				';
			}
			echo '</textarea>';
		}
		echo tag(eLink('jmd_rate_prefs', '', '', '', 'Try again?'), 'p');
	}
	else
	{
		echo tag('Error.', 'h1');
	}

	echo '</div><!--//jmd_rate_prefs-->';
}

// courtesy of upm_savenew <http://utterplush.com/txp-plugins/upm-savenew>
function jmd_rate_prefs_head($buffer)
{
	$find = '</head>';
	$replace = '
		<script type="text/javascript">
		function jmd_rate() {
			var input = document.getElementById("jmd_rate_prefs").getElementsByTagName("input");
			for (i = 0; i < input.length; i++) {
				if (input[i].getAttribute("type") == "text") {
					input[i].onfocus = function() {
						this.select();
					};
				}
			}
			var cssOutput = document.getElementById("jmd_rate_css");
			if (cssOutput) {
				cssOutput.onclick = function() {
					this.select();
				};
			}

		}
		addEvent(window, "load", jmd_rate);
		</script>
		<style type="text/css">
			#jmd_rate_prefs {
				width: 500px;
				margin: 20px auto;
			}
			fieldset label {
				display: block;
			}
		 	#setup form {
				display: inline;
			}
			p.not-ok {
				margin-top: 10px;
			}
		</style>
	';

	return str_replace($find, $replace.$find, $buffer);
}


//--------------------------------------
// public

// instantiates jmd_rate class
function jmd_rate($atts, $thing)
{
    extract(lAtts(array(
        'class' => 'rating',
        'stars' => 4,
        'star_width' => 19,
        'wraptag' => 'div',
    ), $atts));

    global $jmd_rate_instance;
    $jmd_rate_instance = new jmd_rate($stars, $star_width);
    $out = $jmd_rate_instance->getRating().parse($thing);
    
    return ($wraptag) ? doTag($out, $wraptag, $class) : $out;
}

// displays rater
function jmd_rate_display($atts)
{
    return $GLOBALS['jmd_rate_instance']->display();
}

// checks for votes
function if_jmd_rate_votes($atts, $thing)
{
    $condition = ($GLOBALS['jmd_rate_instance']->votes > 0);
    $out = EvalElse($thing, $condition);
    
    return parse($out);
}

// Max rating possible
function jmd_rate_max($atts)
{
    return $GLOBALS['jmd_rate_instance']->maxValue;
}

// returns current rating
function jmd_rate_rating($atts)
{
    return $GLOBALS['jmd_rate_instance']->rating;
}

// returns number of votes
function jmd_rate_votes($atts)
{
    extract(lAtts(array(
        'singular' => '',
        'plural' => '',
    ), $atts));
    $votes = $GLOBALS['jmd_rate_instance']->votes;
    $out = $votes.' ';

    if ($singular && $plural)
    {
        $out .= (($votes > 1) ? $plural : $singular);
    }

    return $out;
}

// checks if the user has voted
function if_jmd_rate_voted($atts, $thing)
{
    $condition = $GLOBALS['jmd_rate_instance']->voted;
    $out = EvalElse($thing, $condition);
    
    return parse($out);
}

// article_custom with sort by rating
function jmd_rate_article($atts)
{
    extract(lAtts(array(
        'author'     => '',
        'category'   => '',
        'form'       => 'default',
        'keywords'   => '',
        'limit'      => '10',
        'max_rating' => '',
        'min_rating' => '',
        'month'      => '',
        'section'    => '',
        'sort'       => 'DESC',
    ), $atts));
    
    $matching = getRows("SELECT parentid, avg(value) AS rating FROM ".safe_pfx('jmd_rate')." GROUP BY parentid HAVING rating BETWEEN $min_rating and $max_rating ORDER BY rating $sort LIMIT $limit");
    // if no articles match the criteria, exit
    if (!$matching)
    {
        return;
    }
    $out = '';
    foreach ($matching as $article)
    {
        $out .= article_custom(array(
            'author'    => $author,
            'category'  => $category,
            'form'      => $form,
            'id'        => $article['parentid'],
            'keywords'  => $keywords,
            'limit'     => $limit,
            'month'     => $month,
            'section'   => $section,
        ));
    }
    
    return $out;
}


class jmd_rate
{
    // input
    private $starWidth;
    public $maxValue;
    // helpers
    private $dbTable, $parentid, $ip, $uri;
    // rating
    private $value, $usedIps;
    public $votes, $voted, $rating;

    public function __construct($stars, $star_width)
    {
        ob_start();
        $this->dbTable = 'jmd_rate';
        $this->parentid = $GLOBALS['thisarticle']['thisid'];
        $this->uri = permlinkurl_id($this->parentid);
        $this->ip = $_SERVER['REMOTE_ADDR'];

        $this->maxValue = $stars;
        $this->starWidth = $star_width;

        if (gps('rating'))
        {
            $this->setRating();
        }
    }

    public function getRating()
    {
        $query = "SELECT sum(value) AS total_value, count(value) AS total_votes, max_value FROM ".safe_pfx($this->dbTable)." WHERE parentid=$this->parentid GROUP BY max_value";
        $row = getRows($query);

        if ($row)
        {
            foreach($row as $r)
            {
                extract($r);
            }

            $this->value = $r['total_value'];
            $this->votes = $r['total_votes'];

            // if the # of stars has changed, adjust previous votes
            if ($r['max_value'] != $this->maxValue)
            {
                $scalar = ($this->maxValue/$r['max_value']);
                // adjust display value
                $this->value *= $scalar;
                // adjust previous votes
                safe_update($this->dbTable, "value=(value*$scalar), max_value=$this->maxValue", "parentid=$this->parentid");
            }

            $this->rating = @number_format($this->value/$this->votes, 2);

            // see if the visitor has voted
            $this->voted = safe_field("ip", $this->dbTable, "ip=INET_ATON('$this->ip') AND parentid=$this->parentid");
        }
    }

    private function setRating()
    {
        $userRating = intval(gps('rating'));
        if ($this->voted)
        {
            echo tag('You have already voted!', 'p', ' class="error"');
        }
        elseif ($userRating > $this->maxValue)
        {
            echo tag('Cheater!', 'p', ' class="error"');
        }
        else
        {
            safe_insert($this->dbTable, "parentid=$this->parentid, value=$userRating, max_value=$this->maxValue, ip=INET_ATON('$this->ip')");
            while (@ob_end_clean());
            if (empty($_SERVER['FCGI_ROLE']) and empty($_ENV['FCGI_ROLE']))
            {
                txp_status_header('303 See Other');
                header('Location: '.$this-> uri);
                header('Connection: close');
                header('Content-Length: 0');
            }
            else
            {
                echo <<<HTML
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html lang="en-us" xml:lang="en-us" xmlns="http://www.w3.org/1999/xhtml">
    <head>
        <meta http-equiv="refresh" content="0;url={$this->uri}"/>
        <title>Redirecting...</title>
    </head>
    <body>
        <a href="{$this->uri}">Back to the article</a>
    </body>
</html>
HTML;
            }
        }
    }

    public function display()
    {
        $out = '<ul style="width: '.$this->maxValue * $this->starWidth.'px">';
        $out .= '<li class="current_rating" style="width: '.$this->rating * $this->starWidth.'px;">Currently rated '.$this->rating.'</li>';
        
        // if they haven't voted and voting is open, link the stars
        if (!$this->voted)
        {
            $getUri = ($GLOBALS['permlink_mode'] == 'messy') ? '&amp;rating=' : '?rating=';
            for ($i = 1; $i <= $this->maxValue; $i++)
            {
                $out .= '<li class="rating_'.$i.'">
                    <a href="'.$this->uri.$getUri.$i.'" rel="nofollow">
                        '.$i.'/'.$this->maxValue.'
                    </a>
                </li>';
            }
        }
        $out .= '</ul>';
        
        return $out;
    }
}

