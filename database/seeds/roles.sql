INSERT INTO roles (id, slug, name, permissions) VALUES
(1, 'admin', 'Administrateur', JSON_ARRAY('*')),
(2, 'editor', 'Éditeur', JSON_ARRAY('content.manage','media.manage','bookings.manage','messages.manage')),
(3, 'viewer', 'Lecteur', JSON_ARRAY('bookings.view','messages.view','content.view'))
ON DUPLICATE KEY UPDATE name=VALUES(name), permissions=VALUES(permissions);
