<?php
/**
 * Zippy
 * The Zip Extraction Tool
 *
 * @author      Daniel Waldmann <dan.waldmann@gmail.com>
 * @copyright   Copyright (c) 2010, Daniel Waldmann
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @version     1.0
 */
 
/**
 * The TODO List
 * 
 * @todo Update the script execution timeout to deal with much larger ZIP files.
 * @todo Add a compatability mode for ZIP extraction using the zip_* functions.
 * @todo Add option in form to use compatability mode.
 * @todo Add warning messages when Zippy cannot run due to libraries not being available
 * @todo Add warning messages when Zippy cannot read or write to the folder
 */

/* -------------------------------------------------------------------------
 * Helper Functions
 * -------------------------------------------------------------------------
 */

function checked($data, $value)
{
    if(is_array($value))
    {
        return (in_array($data, $value))
            ? 'checked="checked"'
            : '';
    }
    else
    {
        return ($data == $value)
            ? 'checked="checked"'
            : '';
    }
}

function selected($data, $value)
{
    if(is_array($value))
    {
        return (in_array($data, $value))
            ? 'selected="selected"'
            : '';
    }
    else
    {
        return ($data == $value)
            ? 'selected="selected"'
            : '';
    }
}

function find_archives()
{
    // Collect all the Zip files
    $archives = glob("*.zip");

    // Remove any items that aren't files.
    foreach($archives as $k => $file)
    {
        if(! is_file($file))
        {
            unset($archives[$k]);
        }
    }

    return $archives;
}

/* -------------------------------------------------------------------------
 * Collect Zip Files
 * -------------------------------------------------------------------------
 */

$archives = find_archives();

/* -------------------------------------------------------------------------
 * Manage POST Data
 * -------------------------------------------------------------------------
 */

// Add the data we want into a new form object
$data = new stdClass();
$data->file     = $archives[$_POST['file']];    // File from the archives array
$data->folder   = $_POST['folder'];             //
$data->sub      = trim($_POST['sub']);          // Sub Folder name
$data->delete   = intval($_POST['delete'], 10); // Delete Zip Archive option
$data->kill     = intval($_POST['kill'], 10);   // Kill Zippy option
$data->extract  = ($_POST['submit'] == 'Extract') ? true : false;

$error = false;
$extract = false;
$path = '.';

if($data->extract && (count($archives) > 0))
{
    // Check if the file submitted was actually in the list provided.
    if(!in_array($data->file, $archives))
    {
        $debug[] = "Invalid Zip Archive, $data->file was not in the options you had availbale";
        $error = true;
    }

    // User chosen to use a sub folder, lets check it out
    if($data->folder == 'other')
    {
        // Check if a sub folder was actually provided
        if($data->sub == '')
        {
            $debug[] = "You've chosen to extract &quot;$data->file&quot; to a sub folder,"
                     . " but haven't provided one, please do so. (P.S. Spaces don't count as a folder name)";
            $error = true;
        }
        // Check for invalid characters, Zippy doesn't like them!
        else if(preg_match( '/[^a-zA-Z0-9\_\-\/]/' , $data->sub))
        {
            $debug[] = "Invalid subfolder details, please use the letters, numbers,"
                     . " underscores, dashes and forward slashes only";
            $error = true;
        } 
        else
        {
            $path = $data->sub;

            # Clean up the path
            $searches = array(
                '/[\/]+/',      // Multiple forward slashes
                '/^[\/]/',      // First character is a slash
            );
            $replacements = array(
                '/',            // Replace the multiple slashes with a single slash
                '',             // Remove the first character slash
            );
            // Run the Cleaner
            $path = preg_replace($searches, $replacements, $path);

            // Append a trailing slash if one doesn't exist
            if(! preg_match('/[\/]$/', $path))
                $path .= '/';
        }
    }

    if($error === false) $extract = true;
}

/* -------------------------------------------------------------------------
 * Dealing with the Zip File
 * -------------------------------------------------------------------------
 */
if($extract === true)
{
    $zip = new ZipArchive;
    $res = $zip->open($data->file);

    // Ok, we have a functional Zip.
    if($res === TRUE)
    {
        $debug[] = "Extracting $data->file&hellip;";

        $zip->extractTo($path);
        $zip->close();

        $debug[] = ($data->folder == 'other') ? 'Extracted to: '.$path : 'Extracted';

        // Zippy, Delete that Archive!
        if($data->delete === 1)
        {
            $debug[] = "Deleted the Zip Archive &quot;$data->file&quot;";
            unlink($data->file);
        }

        // Zippy, Delete Thyself!
        if($data->kill === 1)
        {
            unlink(__FILE__);
            // Attempt to redirect to a new suitable location
            ($data->folder == 'other')
                ? header("Location: $path")
                : header("Location: index.php");
        }

        $debug[] = 'Zippy has finished the tasks';
    }
    //Hmmm... There's something wrong with this Zip
    else
    {
        $debug[] = "Could not open the Zip Archive: $data->file";
    }

    // An attempt to extract a zip file has been run,
    // lets re-collect the list of Archives for the form
    $archives = find_archives();
}

/* -------------------------------------------------------------------------
 * OUTPUT
 * -------------------------------------------------------------------------
 * If you are making any edits to this file, there should be no output to
 * the web browser before this point.
 */

// Compress output with GZip if possible. Otherwise just start buffering nomrally.
if(! ob_start('ob_gzhandler')) ob_start();
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
        <title>Zippy | The .zip file extractor</title>
        <!-- Styles -->
        <style type="text/css">
        /* Reset */
        html, body, div, blockquote, img, label, p, h1, h2, h3, h4, h5, h6, pre, ul, ol, li, dl, dt, dd, form, a, fieldset, input, th, td {
            margin:0px;
            padding:0px;
            border:0px;
            outline:none;
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
        }
        ul {list-style-type: disc;}
        ol {list-style-type: decimal;}

        /* Tags */
        html, body {
            font-size: 13px;
            color: #555;
            line-height: 1.5em;
        }
        h1, h2, h3, h4, h5, h6 {
            font-weight: bold;
        }
        h1 {
            font-size: 20px;
        }
        h2 {
            font-size: 16px;
        }
        h3 {
            font-size: 14px;
        }

        form, p {
            margin: 20px 0px;
        }

        ul {
           margin: 20px 0px 20px 20px;
        }

        small {
            color: #888;
        }

        /* - Form */
        fieldset {
            background:#eee;
            border: 1px solid #ccc;
            padding:20px;
        }
        label {
            display: block
        }

        input, textarea, select {
            font-size: 13px;
        }

        input.text,
        input.submit,
        textarea {
            border:1px solid #888888;
            padding:2px;
        }


        input.submit {
            padding:2px 4px;

            /* CSS3 */
            text-shadow: 0px 1px 0px #cccccc;
            filter: dropshadow(color=#cccccc, offx=0, offy=1);

            background-color: #aaa;
            background-image: -webkit-gradient(
                linear,
                left bottom,
                left top,
                color-stop(0.29, rgb(170,170,170)),
                color-stop(0.83, rgb(204,204,204))
            );
            background-image: -moz-linear-gradient(
                center bottom,
                rgb(170,170,170) 29%,
                rgb(204,204,204) 83%
            );
        }

        input.submit:hover {
            background-color:#ccc;

            /* CSS3 */
            text-shadow: 0px 1px 0px #eeeeee;
            filter: dropshadow(color=#eeeeee, offx=0, offy=1);

            background-image: -webkit-gradient(
                linear,
                left bottom,
                left top,
                color-stop(0.29, rgb(204,204,204)),
                color-stop(0.83, rgb(238,238,238))
            );
            background-image:
            -moz-linear-gradient(
                center bottom,
                rgb(204,204,204) 29%,
                rgb(238,238,238) 83%
            );

            cursor:pointer;
        }

        input.submit:active {
            background-color: #aaa;

            /* CSS3 */
            text-shadow: 0px 1px 0px #cccccc;
            filter: dropshadow(color=#cccccc, offx=0, offy=1);

            background-image: -webkit-gradient(
                linear,
                left bottom,
                left top,
                color-stop(0.29, rgb(204,204,204)),
                color-stop(0.83, rgb(170,170,170))
            );
            background-image: -moz-linear-gradient(
                center bottom,
                rgb(204,204,204) 29%,
                rgb(170,170,170) 83%
            );
        }

        /* Classes */
        /* - Common */
        .inline {
            display:inline;
        }
        .indent-l {
            margin-left: 20px;
        }

        .left {
            float:left;
        }
        .right {
            float:right;
        }
        .clear {
            clear: both;
            line-height: 0px;
            height:0px;
        }
        /* - Form Related */
        .input {
            margin-bottom: 20px;
        }

        fieldset div.left,
        fieldset div.right{
            width:450px;
        }

        fieldset .last {
            margin-bottom: 0px;
        }
        /* IDs */
        #Container {
            width: 960px;
            margin:0px auto;
            padding-top: 20px;
        }
        </style> 
</head>
<body>
    <div id="Container">
        <h1>Welcome to Zippy</h1>
        
        <p>Zippy aims to provide easy .zip archive extractions. The features speak for themselves in the form.<br />
        Here's a small <a href="#Disclaimer">disclaimer</a> of sorts.</p>
        
        <?php if($debug) : ?>
            <?php if($error): ?>
                <!-- Error -->
                <h2>Woah there Zippy!</h2>
                <p>We found some issues with what you were trying to process.</p>
            <?php else : ?>
            	<!-- Success -->
                <h2>Success!</h2>
                <p>Zippy is pleased to announce &quot;<?php echo $data->file; ?>&quot; has been extracted.</p>
            <?php endif; ?>
            
            <!-- Messages -->
            <ul>
	            <?php foreach ($debug as $msg): ?>
	                <li><?php echo $msg; ?></li>
	            <?php endforeach;?>
            </ul>
        <?php endif; ?>
        
        <!-- Zippy Form -->
        <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
            <fieldset>
                <?php if($archives) : ?>
                	<!-- Zip Archive files found -->
                    <div class="left">
                    
                    	<!-- Zip Archives -->
                        <div class="input">
                            <label for="ZipFile">Zip Archive to Extract</label>
                            <select id="ZipFile" name="file">
                                <?php foreach($archives as $k => $filename) : ?>
                                    <option value="<?php echo $k; ?>" <?php echo selected($_POST['file'], $k); ?>><?php echo $filename; ?></option><?php echo PHP_EOL; ?>
                                <?php endforeach; ?>
                            </select>
                        </div>

						<!-- Extraction Location  -->
                        <div class="input">
                            <div>
                                <input id="ZipFolder-Here" name="folder" type="radio" value="here" <?php echo checked($_POST['folder'], array('','here')); ?> />
                                <label class="inline" for="ZipFolder-Here">Extract zip contents here</label>
                            </div>

                            <div>
                                <input id="ZipFolder-Other" name="folder" type="radio" value="other" <?php echo checked($_POST['folder'], 'other'); ?> />
                                <label class="inline" for="ZipFolder-Other">Extract zip to a sub folder</label>
                            </div>

							<!-- Sub Folder -->
                            <div class="indent-l">
                                <label for="SubFolder" class="inline">Sub folder(s)</label>
                                <input id="SubFolder" name="sub" type="text" class="text" value="<?php echo trim($_POST['sub']); ?>" />
                            </div>

                        </div>
						
						<!-- Addtional Options -->
                        <div class="input">
                        	<!-- Delete the Zip Archive -->
                            <input id="DeleteZip" name="delete" type="checkbox" value="1" <?php echo checked($_POST['delete'], 1); ?> />
                            <label class="inline" for="DeleteZip">Delete the Zip Archive after extraction</label><br />
                            
                            <!-- Delete Zippy -->
                            <input id="DeleteZippy" name="kill" type="checkbox" value="1" <?php echo checked($_POST['kill'], 1); ?> />
                            <label class="inline" for="DeleteZippy">Delete Zippy after extraction</label>
                        </div>
                        
                        <!-- Submit buttons -->
                        <div class="input last">
                            <input id="submit" type="submit" class="submit" name="submit" value="Extract" />
                        </div>
                    </div>
                    
                    <!-- Information for users -->
                    <div class="right">
                        <p><strong>Sub Folder Naming:</strong> Please use the letters, numbers, underscores, dashes and forward slashes only. Note: Forward slashes are directory separators.</p>
                        <p><strong>Deleting Zippy:</strong> Upon deleting itself, Zippy will try to redirect you to the root directory of your newly extracted Zip Archive. If you do happen to arrive at an odd location, don't stress, Zippy is new at this and may have just sent you to the wrong place.</p>
                    </div>
                <?php else : ?>
                	<!-- No Zip Archive files found -->
	                <p>Hmmmm&hellip; There appears to be no files for Zippy to extract. Please upload one to the server.</p>
                <?php endif; ?>
                    
                <div class="clear">&nbsp;</div>
            </fieldset>
        </form>
    </div>
</body>
</html>
<?php
// Fin.
ob_end_flush();