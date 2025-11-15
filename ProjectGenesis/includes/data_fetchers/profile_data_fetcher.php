<?php

function getProfileData($pdo, $targetUsername, $currentTab, $currentUserId)
{
    if (empty($targetUsername)) {
        return null;
    }

    $allowedTabs = ['posts', 'likes', 'bookmarks', 'info', 'amigos', 'fotos'];
    if (!in_array($currentTab, $allowedTabs)) {
        $currentTab = 'posts'; 
    }

    try {
        $stmt_profile = $pdo->prepare(
            "SELECT u.id, u.uuid, u.username, u.profile_image_url, u.banner_url, u.role, u.created_at, u.is_online, u.last_seen,
                    u.email, u.bio,
                    COALESCE(p.is_friend_list_private, 1) AS is_friend_list_private, 
                    COALESCE(p.is_email_public, 0) AS is_email_public,
                    COALESCE(p.message_privacy_level, 'all') AS message_privacy_level,
                    p.employment, 
                    p.education
               FROM users u 
               LEFT JOIN user_preferences p ON u.id = p.user_id
               WHERE u.username = ? AND u.account_status = 'active'"
        );
        $stmt_profile->execute([$targetUsername]);
        $userProfile = $stmt_profile->fetch();

        if (!$userProfile) {
            return null;
        }

        $viewProfileData = $userProfile;
        $targetUserId = $userProfile['id'];
        $isOwnProfile = ($targetUserId == $currentUserId);

        if (!$isOwnProfile && ($currentTab === 'likes' || $currentTab === 'bookmarks')) {
            $currentTab = 'posts';
        }
        $viewProfileData['current_tab'] = $currentTab;

        $friendshipStatus = 'not_friends';
        if ($isOwnProfile) {
            $friendshipStatus = 'self';
        } else {
            $userId1 = min($currentUserId, $targetUserId);
            $userId2 = max($currentUserId, $targetUserId);
            $stmt_friend = $pdo->prepare("SELECT status, action_user_id FROM friendships WHERE user_id_1 = ? AND user_id_2 = ?");
            $stmt_friend->execute([$userId1, $userId2]);
            $friendship = $stmt_friend->fetch();
            if ($friendship) {
                if ($friendship['status'] === 'accepted') {
                    $friendshipStatus = 'friends';
                } elseif ($friendship['status'] === 'pending') {
                    $friendshipStatus = ($friendship['action_user_id'] == $currentUserId) ? 'pending_sent' : 'pending_received';
                }
            }
        }
        $viewProfileData['friendship_status'] = $friendshipStatus;

        $viewProfileData['publications'] = [];
        $viewProfileData['profile_friends_preview'] = [];
        $viewProfileData['full_friend_list'] = [];
        $viewProfileData['friend_count'] = 0;
        $viewProfileData['photos'] = [];

        $isFriendListPrivate = (int)($viewProfileData['is_friend_list_private'] ?? 1);

        switch ($currentTab) {
            case 'posts':
            case 'likes':
            case 'bookmarks':
                
                if (!$isFriendListPrivate || $isOwnProfile) {
                    $stmt_count = $pdo->prepare("SELECT COUNT(*) FROM friendships WHERE (user_id_1 = ? OR user_id_2 = ?) AND status = 'accepted'");
                    $stmt_count->execute([$targetUserId, $targetUserId]);
                    $viewProfileData['friend_count'] = (int) $stmt_count->fetchColumn();

                    $stmt_friends = $pdo->prepare(
                        "SELECT u.username, u.profile_image_url, u.role 
                           FROM friendships f
                           JOIN users u ON (CASE WHEN f.user_id_1 = ? THEN f.user_id_2 ELSE f.user_id_1 END) = u.id
                           WHERE (f.user_id_1 = ? OR f.user_id_2 = ?) AND f.status = 'accepted'
                           ORDER BY RAND()
                           LIMIT 9"
                    );
                    $stmt_friends->execute([$targetUserId, $targetUserId, $targetUserId]);
                    $viewProfileData['profile_friends_preview'] = $stmt_friends->fetchAll();
                } else {
                    $viewProfileData['friend_count'] = 0;
                    $viewProfileData['profile_friends_preview'] = [];
                }

                $sql_select_base =
                    "SELECT 
                         p.*, 
                         u.username, 
                         u.profile_image_url,
                         u.role,
                         p.title, 
                         p.privacy_level,
                         c.name AS community_name,
                         (SELECT GROUP_CONCAT(pf.public_url SEPARATOR ',') 
                          FROM publication_attachments pa
                          JOIN publication_files pf ON pa.file_id = pf.id
                          WHERE pa.publication_id = p.id
                          ORDER BY pa.sort_order ASC
                         ) AS attachments,
                         (SELECT COUNT(pv.id) FROM poll_votes pv WHERE pv.publication_id = p.id) AS total_votes,
                         (SELECT pv.poll_option_id FROM poll_votes pv WHERE pv.publication_id = p.id AND pv.user_id = :current_user_id) AS user_voted_option_id,
                         (SELECT COUNT(*) FROM publication_likes pl WHERE pl.publication_id = p.id) AS like_count,
                         (SELECT COUNT(*) FROM publication_likes pl WHERE pl.publication_id = p.id AND pl.user_id = :current_user_id) AS user_has_liked,
                         (SELECT COUNT(*) FROM publication_bookmarks pb WHERE pb.publication_id = p.id AND pb.user_id = :current_user_id) AS user_has_bookmarked,
                         (SELECT COUNT(*) FROM publication_comments pc WHERE pc.publication_id = p.id) AS comment_count";
                         
                         // EL BLOQUE DE HASHTAGS FUE ELIMINADO DE AQUÍ

                $sql_from_base =
                    " FROM community_publications p
                       JOIN users u ON p.user_id = u.id
                       LEFT JOIN communities c ON p.community_id = c.id";

                $sql_join_where_clause = "";
                $params = [':current_user_id' => $currentUserId];

                if ($isOwnProfile && $currentTab === 'likes') {
                    $sql_join_where_clause = " JOIN publication_likes pl ON p.id = pl.publication_id WHERE pl.user_id = :target_user_id AND p.post_status = 'active' ";
                    $params[':target_user_id'] = $targetUserId;
                } elseif ($isOwnProfile && $currentTab === 'bookmarks') {
                    $sql_join_where_clause = " JOIN publication_bookmarks pb ON p.id = pb.publication_id WHERE pb.user_id = :target_user_id AND p.post_status = 'active' ";
                    $params[':target_user_id'] = $targetUserId;
                } else { 
                    $privacyClause = "";
                    if (!$isOwnProfile) {
                        if ($friendshipStatus === 'friends') {
                            $privacyClause = "AND (p.privacy_level = 'public' OR p.privacy_level = 'friends')";
                        } else {
                            $privacyClause = "AND p.privacy_level = 'public'";
                        }
                        $privacyClause .= " AND (p.community_id IS NULL OR c.privacy = 'public' OR c.id IN (SELECT community_id FROM user_communities WHERE user_id = :current_user_id))";
                    }
                    $sql_join_where_clause = " WHERE p.user_id = :target_user_id AND p.post_status = 'active' $privacyClause ";
                    $params[':target_user_id'] = $targetUserId;
                }

                $sql_order = " ORDER BY p.created_at DESC LIMIT 50";
                $sql_posts = $sql_select_base . $sql_from_base . $sql_join_where_clause . $sql_order;

                $stmt_posts = $pdo->prepare($sql_posts);
                $stmt_posts->execute($params);
                $publications = $stmt_posts->fetchAll();

                if (!empty($publications)) {
                    $pollIds = [];
                    foreach ($publications as $key => $post) {
                        if ($post['post_type'] === 'poll') $pollIds[] = $post['id'];
                    }
                    if (!empty($pollIds)) {
                        $placeholders = implode(',', array_fill(0, count($pollIds), '?'));
                        $stmt_options = $pdo->prepare(
                            "SELECT po.publication_id, po.id, po.option_text, COUNT(pv.id) AS vote_count
                             FROM poll_options po
                             LEFT JOIN poll_votes pv ON po.id = pv.poll_option_id
                             WHERE po.publication_id IN ($placeholders)
                             GROUP BY po.publication_id, po.id, po.option_text ORDER BY po.id ASC"
                        );
                        $stmt_options->execute($pollIds);
                        $options = $stmt_options->fetchAll(PDO::FETCH_GROUP | PDO::FETCH_ASSOC);
                        foreach ($publications as $key => $post) {
                            $publications[$key]['poll_options'] = $options[$post['id']] ?? [];
                        }
                    }
                }
                $viewProfileData['publications'] = $publications;
                break;

            case 'amigos':
                if (!$isFriendListPrivate || $isOwnProfile) {
                    $stmt_full_friends = $pdo->prepare(
                        "SELECT 
                             u.id, u.username, u.profile_image_url, u.role,
                             (SELECT COUNT(*) 
                              FROM friendships f_common
                              WHERE 
                                (f_common.user_id_1 = u.id OR f_common.user_id_2 = u.id) 
                                AND f_common.status = 'accepted' 
                                AND (f_common.user_id_1 IN (SELECT user_id_2 FROM friendships WHERE user_id_1 = :current_user_id AND status = 'accepted' UNION SELECT user_id_1 FROM friendships WHERE user_id_2 = :current_user_id AND status = 'accepted') OR f_common.user_id_2 IN (SELECT user_id_2 FROM friendships WHERE user_id_1 = :current_user_id AND status = 'accepted' UNION SELECT user_id_1 FROM friendships WHERE user_id_2 = :current_user_id AND status = 'accepted'))
                             ) AS mutual_friends_count
                           FROM friendships f
                           JOIN users u ON (CASE WHEN f.user_id_1 = :target_user_id THEN f.user_id_2 ELSE f.user_id_1 END) = u.id
                           WHERE (f.user_id_1 = :target_user_id OR f.user_id_2 = :target_user_id) AND f.status = 'accepted'
                           ORDER BY u.username ASC"
                    );
                    $stmt_full_friends->execute([
                        ':current_user_id' => $currentUserId,
                        ':target_user_id' => $targetUserId
                    ]);
                    $viewProfileData['full_friend_list'] = $stmt_full_friends->fetchAll();
                } else {
                    $viewProfileData['full_friend_list'] = [];
                }
                break;

            case 'info':
                break;
                
            case 'fotos':
                $sql_photos = "
                    SELECT 
                        pf.public_url,
                        p.id AS publication_id
                    FROM publication_files pf
                    JOIN publication_attachments pa ON pf.id = pa.file_id
                    JOIN community_publications p ON pa.publication_id = p.id
                    LEFT JOIN communities c ON p.community_id = c.id
                    WHERE
                        p.user_id = :target_user_id
                        AND p.post_status = 'active'
                        AND pf.file_type LIKE 'image/%'
                        AND (
                            :is_own_profile = 1 
                            OR 
                            (
                                :is_own_profile = 0 AND (
                                    p.privacy_level = 'public'
                                    OR
                                    (p.privacy_level = 'friends' AND :friendship_status = 'friends')
                                )
                                AND (
                                    p.community_id IS NULL 
                                    OR 
                                    c.privacy = 'public' 
                                    OR 
                                    c.id IN (SELECT community_id FROM user_communities WHERE user_id = :current_user_id)
                                )
                            )
                        )
                    ORDER BY p.created_at DESC
                    LIMIT 100";
                
                $params_photos = [
                    ':target_user_id' => $targetUserId,
                    ':is_own_profile' => (int)$isOwnProfile,
                    ':friendship_status' => $friendshipStatus,
                    ':current_user_id' => $currentUserId
                ];

                $stmt_photos = $pdo->prepare($sql_photos);
                $stmt_photos->execute($params_photos);
                $viewProfileData['photos'] = $stmt_photos->fetchAll();
                break;
        }

        return $viewProfileData;

    } catch (PDOException $e) {
        logDatabaseError($e, 'router(getProfileData) - view-profile');
        return null;
    }
}
?>