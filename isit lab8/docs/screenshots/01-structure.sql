\dt
SELECT conrelid::regclass AS child, pg_get_constraintdef(oid) AS foreign_key
FROM pg_constraint WHERE contype = 'f' AND connamespace = 'public'::regnamespace
ORDER BY conrelid::regclass::text, conname;
