<div class="users form">
<?= $this->Flash->render('auth') ?>
<?= $this->Form->create() ?>
    <fieldset>
        <legend><?= __('Forgotten Password: Please Enter Your Email') ?></legend>
        <?= $this->Form->input('email') ?>
        <?= $this->Form->input('email_confirm') ?>
    </fieldset>
<?= $this->Form->button(__('Submit')); ?>
<?= $this->Form->end() ?>
</div>