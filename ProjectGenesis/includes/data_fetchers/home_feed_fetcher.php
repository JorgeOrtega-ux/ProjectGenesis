<?php

function getHomeFeedData($pdo, $currentUserId, $communityUuid)
{
    $publications = [];
    $currentCommunityId = null;
    $currentCommunityNameKey = "home.popover.mainFeed"; 
    $userId = $currentUserId; 

    try {
        if ($communityUuid) {
            $stmt_comm = $pdo->prepare("SELECT id, name FROM communities WHERE uuid = ?");
            $stmt_comm->execute([$communityUuid]);
            $community = $stmt_comm->fetch();
            
            if ($community) {
                $currentCommunityId = $community['id'];
                $currentCommunityNameKey = $community['name']; 
                
                $sql_posts = 
                    "SELECT 
                        p.*, 
                        u.username, 
                        u.profile_image_url,
                        u.role,
                        p.title, 
                        p.privacy_level,
                        (SELECT GROUP_CONCAT(pf.public_url SEPARATOR ',') 
                         FROM publication_attachments pa
                         JOIN publication_files pf ON pa.file_id = pf.id
                         WHERE pa.publication_id = p.id
                         ORDER BY pa.sort_order ASC
                        ) AS attachments,
                        (SELECT COUNT(pv.id) FROM poll_votes pv WHERE pv.publication_id = p.id) AS total_votes,
                        (SELECT pv.poll_option_id FROM poll_votes pv WHERE pv.publication_id = p.id AND pv.user_id = ?) AS user_voted_option_id,
                        
                        (SELECT COUNT(*) FROM publication_likes pl WHERE pl.publication_id = p.id) AS like_count,
                        (SELECT COUNT(*) FROM publication_likes pl WHERE pl.publication_id = p.id AND pl.user_id = ?) AS user_has_liked,
                        (SELECT COUNT(*) FROM publication_bookmarks pb WHERE pb.publication_id = p.id AND pb.user_id = ?) AS user_has_bookmarked,
                        (SELECT COUNT(*) FROM publication_comments pc WHERE pc.publication_id = p.id) AS comment_count
                     FROM community_publications p
                     JOIN users u ON p.user_id = u.id
                     WHERE p.community_id = ?
                     AND p.post_status = 'active'
                     AND (
                         p.privacy_level = 'public'
                         OR (p.privacy_level = 'friends' AND (
                             p.user_id = ? 
                             OR p.user_id IN (
                                 (SELECT user_id_2 FROM friendships WHERE user_id_1 = ? AND status = 'accepted')
                                 UNION
                                 (SELECT user_id_1 FROM friendships WHERE user_id_2 = ? AND status = 'accepted')
                             )
                         ))
                         OR (p.privacy_level = 'private' AND p.user_id = ?)
                     )
                     ORDER BY p.created_at DESC
                     LIMIT 50";
                
                $stmt_posts = $pdo->prepare($sql_posts);
                $stmt_posts->execute([$userId, $userId, $userId, $currentCommunityId, $userId, $userId, $userId, $userId]);
                $publications = $stmt_posts->fetchAll();
            } else {
                $communityUuid = null; 
            }
        }
        
        if ($communityUuid === null) {
            $sql_posts = 
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
                    (SELECT COUNT(*) FROM publication_comments pc WHERE pc.publication_id = p.id) AS comment_count
                 FROM community_publications p
                 JOIN users u ON p.user_id = u.id
                 LEFT JOIN communities c ON p.community_id = c.id
                 WHERE 
                 p.post_status = 'active'
                 AND (
                    p.community_id IS NULL
                    OR
                    (
                        p.community_id IS NOT NULL 
                        AND (
                            c.privacy = 'public'
                            OR p.community_id IN (SELECT community_id FROM user_communities WHERE user_id = :current_user_id)
                        )
                    )
                 )
                 AND (
                     p.privacy_level = 'public'
                     OR (p.privacy_level = 'friends' AND (
                         p.user_id = :current_user_id
                         OR p.user_id IN (
                             (SELECT user_id_2 FROM friendships WHERE user_id_1 = :current_user_id AND status = 'accepted')
                             UNION
                             (SELECT user_id_1 FROM friendships WHERE user_id_2 = :current_user_id AND status = 'accepted')
                         )
                     ))
                     OR (p.privacy_level = 'private' AND p.user_id = :current_user_id)
                 )
                 ORDER BY p.created_at DESC
                 LIMIT 50";
            
            $stmt_posts = $pdo->prepare($sql_posts);
            $stmt_posts->execute([':current_user_id' => $userId]);
            $publications = $stmt_posts->fetchAll();
        }
        
        if (!empty($publications)) {
            $pollIds = [];
            foreach ($publications as $key => $post) {
                if ($post['post_type'] === 'poll') {
                    $pollIds[] = $post['id'];
                }
            }
            
            if (!empty($pollIds)) {
                $placeholders = implode(',', array_fill(0, count($pollIds), '?'));
                
                $stmt_options = $pdo->prepare(
                   "SELECT 
                        po.publication_id, 
                        po.id, 
                        po.option_text, 
                        COUNT(pv.id) AS vote_count
                    FROM poll_options po
                    LEFT JOIN poll_votes pv ON po.id = pv.poll_option_id
                    WHERE po.publication_id IN ($placeholders)
                    GROUP BY po.publication_id, po.id, po.option_text 
                    ORDER BY po.id ASC"
                );
                $stmt_options->execute($pollIds);
                $options = $stmt_options->fetchAll(PDO::FETCH_GROUP | PDO::FETCH_ASSOC);

                foreach ($publications as $key => $post) {
                    if (isset($options[$post['id']])) {
                        $publications[$key]['poll_options'] = $options[$post['id']];
                    } else {
                        $publications[$key]['poll_options'] = [];
                    }
                }
            }
        }
        
    } catch (PDOException $e) {
        logDatabaseError($e, 'home_feed_fetcher - fetch posts');
        $publications = []; 
    }

    return [
        'publications' => $publications,
        'currentCommunityId' => $currentCommunityId,
        'currentCommunityNameKey' => $currentCommunityNameKey,
        'communityUuid' => $communityUuid
    ];
}
?>