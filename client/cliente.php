<?php
/*
 * Cliente (Prueba de PHP XML-RPC)
 */
?>

<h1>Cliente AfinimaKi en PHP</h1>

<?php
include 'AfinimaKi.php';

define ('API_KEY', 'your_api_key');
define ('API_SECRET', 'your_api_secret');
define ('USER_ID', 'user_id_integer');

define ('HOST', 'localhost');
define ('PORT', '8081');
define ('PATH', '/RPC2');
define ('DEBUG', 0);

if ($_POST) {
    $datos = new AfinimaKi(array(
            'api_key' => API_KEY,
            'api_secret' => API_SECRET,
            'debug' => DEBUG,
            'host' => HOST,
            'port' => PORT,
            'path' => PATH
        )
    );
}
?>

<fieldset>
    <legend>Set Rate</legend>
    <form name="formulario" method="post" action="cliente.php">
        <label>item_id: </label><input type="text" name="item_id" /><br />
        <label>rate: </label><input type="text" name="rate" /><br />
        <input type="submit" name="set_rate" value="set_rate" />
    </form>
<?php
if (isset($_POST['set_rate'])) {
    $ts = time();
    echo $datos->set_rate(USER_ID, $_POST['item_id'], $_POST['rate'], $ts);
}
?>
</fieldset>


<fieldset>
    <legend>Estimate rate</legend>
    <form name="formulario" method="post" action="cliente.php">
        <label>item_id: </label><input type="text" name="item_id" /><br />
        <input type="submit" name="estimate_rate" value="estimate_rate" />
    </form>
<?php
if (isset ($_POST['estimate_rate'])) {
    echo $datos->estimate_rate(USER_ID, $_POST['item_id']);
}
?>
</fieldset>


<fieldset>
    <legend>Estimate multiple rate</legend>
    <form name="formulario" method="post" action="cliente.php">
        <label>item_id1: </label><input type="text" name="item_id1" /><br />
        <label>item_id2: </label><input type="text" name="item_id2" /><br />
        <label>item_id3: </label><input type="text" name="item_id3" /><br />
        <label>item_id4: </label><input type="text" name="item_id4" /><br />
        <input type="submit" name="estimate_multiple_rates" value="estimate_multiple_rates" />
    </form>
<?php
if (isset ($_POST['estimate_multiple_rates'])) {
    $items = array($_POST['item_id1'], $_POST['item_id2'], $_POST['item_id3'], $_POST['item_id4']);
    echo '<pre>';
    print_r($datos->estimate_multiple_rates(USER_ID, $items));
    echo '</pre>';
}
?>
</fieldset>


<fieldset>
    <legend>Get Recommendations</legend>
    <form name="formulario" method="post" action="cliente.php">
        <label>boolean: </label><input type="text" name="boolean" value="1" /><br />
        <input type="submit" name="get_recommendations" value="get_recommendations" />
    </form>
<?php

if (isset ($_POST['get_recommendations'])) {
    echo '<pre>';
    print_r($datos->get_recommendations(USER_ID, $_POST['boolean']));
    echo '</pre>';
}
?>
</fieldset>

<fieldset>
    <legend>Add to wish list</legend>
    <form name="formulario" method="post" action="cliente.php">
         <label>item_id: </label><input type="text" name="item_id" /><br />
        <input type="submit" name="add_to_wishlist" value="add_to_wishlist" />
    </form>
<?php
if (isset ($_POST['add_to_wishlist'])) {
    $ts = (int) time();
    echo $datos->add_to_wishlist(USER_ID, $_POST['item_id'], $ts);
}
?>
</fieldset>

<fieldset>
    <legend>Add to black list</legend>
    <form name="formulario" method="post" action="cliente.php">
         <label>item_id: </label><input type="text" name="item_id" /><br />
        <input type="submit" name="add_to_blacklist" value="add_to_blacklist" />
    </form>
<?php
if (isset ($_POST['add_to_blacklist'])) {
    $ts = (int) time();
    echo $datos->add_to_blacklist(USER_ID, $_POST['item_id'], $ts);
}
?>
</fieldset>

<fieldset>
    <legend>Remove from lists</legend>
    <form name="formulario" method="post" action="cliente.php">
         <label>item_id: </label><input type="text" name="item_id" /><br />
        <input type="submit" name="remove_from_lists" value="remove_from_lists" />
    </form>
<?php
if (isset ($_POST['remove_from_lists'])) {
    $ts = (int) time();
    echo $datos->remove_from_lists(USER_ID, $_POST['item_id'], $ts);
}
?>
</fieldset>

<fieldset>
    <legend>Get user vs user afinimaki</legend>
    <form name="formulario" method="post" action="cliente.php">
         <label>user_id_2: </label><input type="text" name="user_id_2" /><br />
        <input type="submit" name="get_user_user_afinimaki" value="get_user_user_afinimaki" />
    </form>
<?php
if (isset ($_POST['get_user_user_afinimaki'])) {
    echo $datos->get_user_user_afinimaki(USER_ID, $_POST['user_id_2']);
}
?>
</fieldset>

<fieldset>
    <legend>Get soul mates</legend>
    <form name="formulario" method="post" action="cliente.php">
        <input type="submit" name="get_soul_mates" value="get_soul_mates" />
    </form>
<?php
if (isset ($_POST['get_soul_mates'])) {
    echo '<pre>';
    print_r($datos->get_soul_mates(USER_ID));
    echo '</pre>';

    
}
?>
</fieldset>
