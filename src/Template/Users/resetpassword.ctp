<div class="users form">
<?= $this->Flash->render('auth') ?>
<?= $this->Form->create() ?>
    <fieldset>
        <legend><?= __('New Password:') ?></legend>
        <?= $this->Form->input('password') ?>
        <div class="input text required"><label for="password-confirm">Password Confirm</label>
        <?= $this->Form->password('password_confirm',['label'=>['text'=>'Confirm Password']]);?>
        </div>
    </fieldset>
<?= $this->Form->button(__('Submit')); ?>
<?= $this->Form->end() ?>
</div>