INSERT INTO account_closure_steps (closure_id,owner_module,status)
SELECT id,'Hearth','pending'
FROM account_closures
WHERE status IN ('pending_cancellation','processing')
ON DUPLICATE KEY UPDATE owner_module=VALUES(owner_module);
