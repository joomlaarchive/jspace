<?php
/**
 * @copyright   Copyright (C) 2014 KnowledgeArc Ltd. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

defined('JPATH_BASE') or die;

JFactory::getDocument()->addScript(JUri::root().'media/com_jspace/js/jspace.js');

$attr = array(
        'id'          => 'jform_metadatafieldnames',
        'list.select' => 1,
        'list.attr'   => 'class="chzn-custom-value" '
        . 'data-custom_group_text="custom group text" '
        . 'data-no_results_text="' . JText::_('COM_MODULES_ADD_CUSTOM_POSITION') . '" '
        . 'data-placeholder="' . JText::_('COM_MODULES_TYPE_OR_SELECT_POSITION') . '" '
);

$options = array();
$options[] = JHtml::_('select.option', "Red", "Red");
?>
<div class="controls">
<?php
echo JHtml::_('select.genericlist', $options, 'jform[metadatafieldnames]', $attr);
?>
</div>


<div class="controls">
	<select style="display: none;" id="jform_position" name="jform[position]" class="chzn-custom-value chzn-done" data-custom_group_text="Custom Position" data-no_results_text="Add custom position" data-placeholder="Type or Select a Position">
    <optgroup label="">
        <option value=""></option>
    </optgroup>
    <optgroup label="Beez3">
        <option value="debug">Debug [debug]</option>
        <option value="position-0">Search [position-0]</option>
        <option value="position-1">Top [position-1]</option>
        <option value="position-2">Breadcrumbs [position-2]</option>
        <option value="position-3">Right bottom [position-3]</option>
        <option value="position-4">Left middle [position-4]</option>
        <option value="position-5">Left bottom [position-5]</option>
        <option value="position-6">Right top [position-6]</option>
        <option value="position-7">Left top [position-7]</option>
        <option value="position-8">Right middle [position-8]</option>
        <option value="position-9">Footer top [position-9]</option>
        <option value="position-10">Footer middle [position-10]</option>
        <option value="position-11">Footer bottom [position-11]</option>
        <option value="position-12">Middle top [position-12]</option>
        <option value="position-13">Unused [position-13]</option>
        <option value="position-14">Footer last [position-14]</option>
    </optgroup>
    <optgroup label="Jspaceui">
        <option value="navbar">Navbar [navbar]</option>
        <option value="header">Header [header]</option>
        <option value="hero">Hero [hero]</option>
        <option value="top">Top [top]</option>
        <option value="above-content">Above Content [above-content]</option>
        <option value="breadcrumb" selected="selected">Breadcrumb [breadcrumb]</option>
        <option value="component">Component [component]</option>
        <option value="below-content">Below Content [below-content]</option>
        <option value="bottom">Bottom [bottom]</option>
        <option value="left">Left [left]</option>
        <option value="right">Right [right]</option>
        <option value="footer">Footer [footer]</option>
    </optgroup>
    <optgroup label="Knowledgearc">
        <option value="navbar">Navbar [navbar]</option>
        <option value="header">Header [header]</option>
        <option value="hero">Hero [hero]</option>
        <option value="top">Top [top]</option>
        <option value="above-content">Above Content [above-content]</option>
        <option value="breadcrumb" selected="selected">Breadcrumb [breadcrumb]</option>
        <option value="component">Component [component]</option>
        <option value="below-content">Below Content [below-content]</option>
        <option value="bottom">Bottom [bottom]</option>
        <option value="left">Left [left]</option>
        <option value="right">Right [right]</option>
        <option value="footer">Footer [footer]</option>
    </optgroup>
    <optgroup label="Protostar">
        <option value="banner">Banner [banner]</option>
        <option value="debug">Debug [debug]</option>
        <option value="position-0">Search [position-0]</option>
        <option value="position-1">Navigation [position-1]</option>
        <option value="position-2">Breadcrumbs [position-2]</option>
        <option value="position-3">Top centre [position-3]</option>
        <option value="position-4">Unused [position-4]</option>
        <option value="position-5">Unused [position-5]</option>
        <option value="position-6">Unused [position-6]</option>
        <option value="position-7">Right [position-7]</option>
        <option value="position-8">Left [position-8]</option>
        <option value="position-9">Unused [position-9]</option>
        <option value="position-10">Unused [position-10]</option>
        <option value="position-11">Unused [position-11]</option>
        <option value="position-12">Unused [position-12]</option>
        <option value="position-13">Unused [position-13]</option>
        <option value="position-14">Unused [position-14]</option>
        <option value="footer">Footer [footer]</option>
    </optgroup>
    <optgroup label="Custom Position">
        <option value="breadcrumb" selected="selected">breadcrumb</option>
        <option value="header">header</option>
        <option value="left">left</option>
        <option value="position-7">position-7</option>
        <option value="position-8">position-8</option>
    </optgroup>
</select><div id="jform_position_chzn" title="" style="width: 220px;" class="chzn-container chzn-container-single"><a class="chzn-single chzn-single-with-deselect" tabindex="-1"><span>breadcrumb</span><abbr class="search-choice-close"></abbr><div><b></b></div></a><div class="chzn-drop"><div class="chzn-search"><input autocomplete="off" type="text"></div><ul class="chzn-results"><li class="group-result">Beez3</li><li class="active-result group-option" style="" data-option-array-index="3">Debug [debug]</li><li class="active-result group-option" style="" data-option-array-index="4">Search [position-0]</li><li class="active-result group-option" style="" data-option-array-index="5">Top [position-1]</li><li class="active-result group-option" style="" data-option-array-index="6">Breadcrumbs [position-2]</li><li class="active-result group-option" style="" data-option-array-index="7">Right bottom [position-3]</li><li class="active-result group-option" style="" data-option-array-index="8">Left middle [position-4]</li><li class="active-result group-option" style="" data-option-array-index="9">Left bottom [position-5]</li><li class="active-result group-option" style="" data-option-array-index="10">Right top [position-6]</li><li class="active-result group-option" style="" data-option-array-index="11">Left top [position-7]</li><li class="active-result group-option" style="" data-option-array-index="12">Right middle [position-8]</li><li class="active-result group-option" style="" data-option-array-index="13">Footer top [position-9]</li><li class="active-result group-option" style="" data-option-array-index="14">Footer middle [position-10]</li><li class="active-result group-option" style="" data-option-array-index="15">Footer bottom [position-11]</li><li class="active-result group-option" style="" data-option-array-index="16">Middle top [position-12]</li><li class="active-result group-option" style="" data-option-array-index="17">Unused [position-13]</li><li class="active-result group-option" style="" data-option-array-index="18">Footer last [position-14]</li><li class="group-result">Jspaceui</li><li class="active-result group-option" style="" data-option-array-index="20">Navbar [navbar]</li><li class="active-result group-option" style="" data-option-array-index="21">Header [header]</li><li class="active-result group-option" style="" data-option-array-index="22">Hero [hero]</li><li class="active-result group-option" style="" data-option-array-index="23">Top [top]</li><li class="active-result group-option" style="" data-option-array-index="24">Above Content [above-content]</li><li class="active-result group-option" style="" data-option-array-index="25">Breadcrumb [breadcrumb]</li><li class="active-result group-option" style="" data-option-array-index="26">Component [component]</li><li class="active-result group-option" style="" data-option-array-index="27">Below Content [below-content]</li><li class="active-result group-option" style="" data-option-array-index="28">Bottom [bottom]</li><li class="active-result group-option" style="" data-option-array-index="29">Left [left]</li><li class="active-result group-option" style="" data-option-array-index="30">Right [right]</li><li class="active-result group-option" style="" data-option-array-index="31">Footer [footer]</li><li class="group-result">Knowledgearc</li><li class="active-result group-option" style="" data-option-array-index="33">Navbar [navbar]</li><li class="active-result group-option" style="" data-option-array-index="34">Header [header]</li><li class="active-result group-option" style="" data-option-array-index="35">Hero [hero]</li><li class="active-result group-option" style="" data-option-array-index="36">Top [top]</li><li class="active-result group-option" style="" data-option-array-index="37">Above Content [above-content]</li><li class="active-result group-option" style="" data-option-array-index="38">Breadcrumb [breadcrumb]</li><li class="active-result group-option" style="" data-option-array-index="39">Component [component]</li><li class="active-result group-option" style="" data-option-array-index="40">Below Content [below-content]</li><li class="active-result group-option" style="" data-option-array-index="41">Bottom [bottom]</li><li class="active-result group-option" style="" data-option-array-index="42">Left [left]</li><li class="active-result group-option" style="" data-option-array-index="43">Right [right]</li><li class="active-result group-option" style="" data-option-array-index="44">Footer [footer]</li><li class="group-result">Protostar</li><li class="active-result group-option" style="" data-option-array-index="46">Banner [banner]</li><li class="active-result group-option" style="" data-option-array-index="47">Debug [debug]</li><li class="active-result group-option" style="" data-option-array-index="48">Search [position-0]</li><li class="active-result group-option" style="" data-option-array-index="49">Navigation [position-1]</li><li class="active-result group-option" style="" data-option-array-index="50">Breadcrumbs [position-2]</li><li class="active-result group-option" style="" data-option-array-index="51">Top centre [position-3]</li><li class="active-result group-option" style="" data-option-array-index="52">Unused [position-4]</li><li class="active-result group-option" style="" data-option-array-index="53">Unused [position-5]</li><li class="active-result group-option" style="" data-option-array-index="54">Unused [position-6]</li><li class="active-result group-option" style="" data-option-array-index="55">Right [position-7]</li><li class="active-result group-option" style="" data-option-array-index="56">Left [position-8]</li><li class="active-result group-option" style="" data-option-array-index="57">Unused [position-9]</li><li class="active-result group-option" style="" data-option-array-index="58">Unused [position-10]</li><li class="active-result group-option" style="" data-option-array-index="59">Unused [position-11]</li><li class="active-result group-option" style="" data-option-array-index="60">Unused [position-12]</li><li class="active-result group-option" style="" data-option-array-index="61">Unused [position-13]</li><li class="active-result group-option" style="" data-option-array-index="62">Unused [position-14]</li><li class="active-result group-option" style="" data-option-array-index="63">Footer [footer]</li><li class="group-result">Custom Position</li><li class="active-result result-selected group-option" style="" data-option-array-index="65">breadcrumb</li><li class="active-result group-option" style="" data-option-array-index="66">header</li><li class="active-result group-option" style="" data-option-array-index="67">left</li><li class="active-result group-option" style="" data-option-array-index="68">position-7</li><li class="active-result group-option" style="" data-option-array-index="69">position-8</li></ul></div></div></div>

<div
    class="jspace-control-group"
    data-jspace-name="<?php echo $displayData->name; ?>"
    data-jspace-maximum="<?php echo $displayData->maximum; ?>">
    <?php foreach ($displayData->value as $key=>$value) : ?>
    <div
        class="jspace-control"
        data-jspace-name="<?php echo $displayData->name; ?>[<?php echo $key; ?>]">
        <input
            type="text"
            name="<?php echo $displayData->name; ?>[<?php echo $key; ?>][name]"
            value="<?php echo JArrayHelper::getValue($value, 'name'); ?>"/>

        <textarea
            name="<?php echo $displayData->name; ?>[<?php echo $key; ?>][value]"><?php echo JArrayHelper::getValue($value, 'value'); ?></textarea>

        <button class="btn jspace-remove-field" type="button">
            <span class="icon-minus"></span>
        </button>
    </div>
    <?php endforeach; ?>

    <?php if (!count($displayData->value)) : ?>
    <div
        class="jspace-control"
        data-jspace-name="<?php echo $displayData->name; ?>[<?php echo count($displayData->value); ?>]">
        <input
            type="text"
            name="<?php echo $displayData->name; ?>[<?php echo count($displayData->value); ?>][name]"/>
        <textarea
            name="<?php echo $displayData->name; ?>[<?php echo count($displayData->value); ?>][value]"></textarea>

        <button class="btn jspace-remove-field" type="button">
            <span class="icon-minus"></span>
        </button>
    </div>
    <?php endif; ?>

    <button
        class="btn jspace-add-field hasTooltip"
        type="button"
        data-title="<?php echo JText::_('COM_JSPACE_ADD_DESC'); ?>">
        <span class="icon-plus"></span>
    </button>
</div>
