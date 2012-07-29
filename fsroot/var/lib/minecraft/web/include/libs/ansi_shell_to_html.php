<?php

class ansi_shell_to_html {
  //Based on http://en.wikipedia.org/wiki/ANSI_escape_code , the colors from xterm
  public static function ansi_escape_code_to_css_style($a1, $a2) {
    $style = "";
    if ($a1 === 0) {
      if ($a2 >= 30 && $a2 <= 37) {
	$map = Array(30 => "rgb(0,0,0)", //black
		     31 => "rgb(205,0,0)", //red
		     32 => "rgb(0,205,0)", //green
		     33 => "rgb(205,205, 0)", //yellow
		     34 => "rgb(0,0,238)", //blue
		     35 => "rgb(205,0,205)", //magenta
		     36 => "rgb(0,205,205)", //cyan
		     37 => "rgb(229,229,229)", //gray
		     );
	$style .= "color: " . $map[$a2];
      }
    } else if ($a1 === 1) {
      //same as $a1===0, except lighter
      if ($a2 >= 30 && $a2 <= 37) {
	$map = Array(30 => "rgb(127,127,127)", //black
		     31 => "rgb(255,0,0)", //red
		     32 => "rgb(0,255,0)", //green
		     33 => "rgb(255,255, 0)", //yellow
		     34 => "rgb(92,92,255)", //blue
		     35 => "rgb(255,0,255)", //magenta
		     36 => "rgb(0,255,255)", //cyan
		     37 => "rgb(255,255,255)", //gray
		     );
	$style .= "color: " . $map[$a2];
      }
    } //many others have meanings; ignore them for now

    return $style;
  }

  public static function cmdline_to_html($str) {
    preg_match_all("/\x1B\\[(\\d+);(\\d+)m(.*?)\x1B\\[0m/", $str, $matches, PREG_OFFSET_CAPTURE | PREG_SET_ORDER);

    $out = "";
    $i = 0;
    foreach ($matches as $set) {
      $s_whole = $set[0];
      $s_style1 = $set[1];
      $s_style2 = $set[2];
      $s_text = $set[3];

      if ($s_whole[1] > $i) {
	//append from previous progress up to colorcode start
	$out .= substr($str, $i, $s_whole[1] - $i);
      }

      //Add HTML color start
      $style = self::ansi_escape_code_to_css_style((int) $s_style1[0], (int) $s_style2[0]);
      $out .= sprintf("<span style=\"%s\">", htmlentities($style));

      //Add text
      $out .= htmlentities($s_text[0]);

      //Add HTML color end
      $out .= "</span>";

      //Set pointer to after this match
      $i = $s_whole[1] + strlen($s_whole[0]);
    }

    //Append text after last match (which is string start if no matches)
    $out .= htmlentities(substr($str, $i));
    $i = strlen($str);

    return $out;
  }
}

?>