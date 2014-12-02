<?php
$categoryId = $displayData->getCategoryId();

$groups = $displayData->getUserGroups();
$schemas = \JSpace\Factory::getSchemas();
?>

<div id="schemas-sliders" class="tabbable tabs-left">
    <ul class="nav nav-tabs">
    <?php foreach ($groups as $group) : ?>
        <li<?php echo ($group->value == 1) ? ' class="active"' : ''; ?>>
            <a href="#schema-<?php echo $group->value; ?>" data-toggle="tab">
            <?php echo str_repeat('<span class="level">&ndash;</span> ', $curLevel = $group->level) . $group->text; ?>
            </a>
        </li>
    <?php endforeach; ?>
    </ul>

    <div class="tab-content">
        <?php foreach ($groups as $group) : ?>
        <div
            class="tab-pane<?php echo ($group->value == 1) ? ' active' : ''; ?>"
            id="schema-<?php echo $group->value; ?>">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th
                            class="actions"
                            id="actions-th<?php echo $group->value; ?>">
                            <span class="acl-action"><?php echo  JText::_('COM_JSPACE_SCHEMAS_SCHEMA'); ?></span>
                        </th>

                        <th
                            class="settings"
                            id="settings-th<?php echo $group->value; ?>">
                            <span class="acl-action"><?php echo JText::_('COM_JSPACE_SCHEMAS_NEWSETTING'); ?> </span>
                        </th>
                        <th id="aclactionth<?php echo $group->value; ?>">
                            <span
                                class="acl-action"><?php echo JText::_('COM_JSPACE_SCHEMAS_CALCULATEDSETTING'); ?></span>
                        </th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($schemas as $schema) : ?>
                    <tr>
                        <td headers="actions-th<?php echo $group->value; ?>">
                            <label
                                for="<?php echo $displayData->id.'_'.$schema->name.'_'.$group->value; ?>" class="hasTooltip"
                                title="<?php echo htmlspecialchars(JText::_($schema->label).' '.JText::_($schema->description), ENT_COMPAT, 'UTF-8'); ?>">
                                <?php echo JText::_($schema->label); ?>
                            </label>
                        </td>

                        <td headers="settings-th<?php echo $group->value; ?>">
                            <select
                                class="input-small"
                                name="<?php echo $displayData->name.'['.$schema->name.']['.$group->value.']'; ?>" id="<?php echo $displayData->id.'_'.$schema->name.'_'.$group->value; ?>"
                                title="<?php echo JText::sprintf('JLIB_RULES_SELECT_ALLOW_DENY_GROUP', JText::_($schema->label), trim($group->text)); ?>">

                                <?php
                                $inheritedRule = $schema->canUse($group->value, $categoryId);

                                $schemaRule = $schema->isUsed($group->value, $categoryId);
                                ?>

                                <?php if (!empty($group->parent_id)) : ?>
                                <option
                                    value=""
                                    <?php echo ($schemaRule === -1 ? ' selected="selected"' : ''); ?>><?php echo JText::_('COM_JSPACE_SCHEMA_INHERITED'); ?></option>
                                <?php endif; ?>
                                <option
                                    value="1"
                                    <?php echo ($schemaRule === 1 ? ' selected="selected"' : ''); ?>><?php echo JText::_('COM_JSPACE_SCHEMA_USE'); ?></option>
                                <option
                                    value="0"
                                    <?php echo ($schemaRule === 0 ? ' selected="selected"' : ''); ?>><?php echo JText::_('COM_JSPACE_SCHEMA_DONTUSE'); ?></option>
                            </select>
                        </td>

                        <td headers="aclactionth<?php echo $group->value; ?>">
                            <?php if ($inheritedRule === true) : ?>
                            <span
                                class="label label-success">
                                <?php echo JText::_('COM_JSPACE_SCHEMA_USE'); ?></span>
                            <?php else : ?>
                            <span
                                class="label label-important">
                                <?php echo JText::_('COM_JSPACE_SCHEMA_DONTUSE'); ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<div class="alert alert-info"><?php echo JText::_('PLG_CONTENT_JSPACESCHEMAS_FIELDSET_NOTES'); ?></div>