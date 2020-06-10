<form class="default" action="<?php echo $controller->link_for('phonebook_holder_groups/store') ?>" method="post">
    <label for="groups">
        <?php echo dgettext('phonebook',
            'Welche Statusgruppen sollen berücksichtigt werden, um Einrichtungsleitungen zu finden?') ?>
    </label>
    <select name="groups[]" id="groups" class="nested-select" multiple>
        <option value="">
            -- <?php echo dgettext('phonebook', 'bitte auswählen') ?> --
        </option>
        <?php foreach ($allgroups as $group) : ?>
            <option value="<?php echo htmlReady($group) ?>"<?php echo in_array($group, $selected) ? ' selected' : '' ?>>
                <?php echo htmlReady($group) ?>
            </option>
        <?php endforeach ?>
    </select>
    <footer data-dialog-button>
        <?php echo Studip\Button::createAccept(dgettext('phonebook', 'Speichern'),
            'submit') ?>
        <?= Studip\LinkButton::createCancel(dgettext('phonebook', 'Abbrechen'),
            $controller->link_for('phonebook_search'), ['data-dialog-close' => true]) ?>
    </footer>
</form>
