<div class="users form">
<?= $this->Form->create($user) ?>
    <fieldset>
        <legend><?= __('User Signup') ?></legend>
        <?= $this->Form->input('email') ?>
        <?= $this->Form->input('email_confirm') ?>
        <?= $this->Form->input('password') ?>
        <div class="input text required"><label for="password-confirm">Password Confirm</label>
        <?= $this->Form->password('password_confirm',['label'=>['text'=>'Confirm Password']]);?>
        </div>
        <?= $this->Form->input('termsofservice', array('type' => 'checkbox', 'label'=>'I have read and agree to the terms of service.')); ?>
 
        <?= $this->Form->input('promotions', array('type' => 'checkbox', 'label'=>'I am interesting in receiving email promotions and offers.')); ?>
   </fieldset>
<?= $this->Form->button(__('Submit')); ?>
<?= $this->Form->end() ?>
</div>