<?php

namespace Modules\ManajemenMahasiswa\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\ManajemenMahasiswa\Models\Discussion;
use Modules\ManajemenMahasiswa\Services\ForumService;

class ForumController extends Controller
{
    public function __construct(private ForumService $forumService) {}

    // Forum Categories (Kategori Forum)

    public function index()
    {
        $forums = $this->forumService->listForums();
        return view('manajemenmahasiswa::forum.index', compact('forums'));
    }

    public function storeForum(Request $request)
    {
        $this->authorizeAdminOrKoor();

        $validated = $request->validate([
            'nama_forum' => 'required|string|max:255',
            'deskripsi'  => 'nullable|string',
        ]);

        $this->forumService->createForum($validated);

        return back()->with('success', 'Forum berhasil ditambahkan.');
    }

    public function updateForum(Request $request, int $id)
    {
        $this->authorizeAdminOrKoor();

        $validated = $request->validate([
            'nama_forum' => 'required|string|max:255',
            'deskripsi'  => 'nullable|string',
        ]);

        $this->forumService->updateForum($id, $validated);

        return back()->with('success', 'Forum berhasil diperbarui.');
    }

    public function removeForum(int $id)
    {
        $this->authorizeAdminOrKoor();

        $this->forumService->deleteForum($id);

        return back()->with('success', 'Forum berhasil dihapus.');
    }


    // Discussions (Diskusi/Thread)
    
    public function showForum(int $forumId, Request $request)
    {
        $forum = $this->forumService->findForum($forumId);
        $filters = $request->only(['search', 'status']);
        $discussions = $this->forumService->listDiscussions($forumId, $filters);

        return view('manajemenmahasiswa::forum.show', compact('forum', 'discussions'));
    }

    public function storeDiscussion(Request $request, int $forumId)
    {
        $validated = $request->validate([
            'judul' => 'required|string|max:255',
            'isi_diskusi' => 'required|string|min:10',
        ]);

        $discussion = $this->forumService->createDiscussion(Auth::id(), $forumId, $validated);

        return redirect()->route('manajemenmahasiswa.forum.discussion.show', [$forumId, $discussion->id])
                         ->with('success', 'Diskusi berhasil dibuat.');
    }

    public function showDiscussion(int $forumId, int $discussionId)
    {
        $discussion = $this->forumService->findDiscussion($discussionId);
        $comments = $this->forumService->listComments($discussionId);

        return view('manajemenmahasiswa::forum.discussion_show', compact('discussion', 'comments'));
    }

    public function updateDiscussion(Request $request, int $forumId, int $discussionId)
    {
        $discussion = $this->forumService->findDiscussion($discussionId);
        if (Auth::id() !== $discussion->user_id) {
            $this->authorizeAdminOrKoor();
        }

        $validated = $request->validate([
            'judul' => 'required|string|max:255',
            'isi_diskusi' => 'required|string|min:10',
        ]);

        $this->forumService->updateDiscussion($discussionId, $validated);

        return back()->with('success', 'Diskusi berhasil diperbarui.');
    }

    public function removeDiscussion(int $forumId, int $discussionId)
    {
        $discussion = $this->forumService->findDiscussion($discussionId);
        if (Auth::id() !== $discussion->user_id) {
            $this->authorizeAdminOrKoor();
        }

        $this->forumService->deleteDiscussion($discussionId);

        return redirect()->route('manajemenmahasiswa.forum.show', $forumId)
                         ->with('success', 'Diskusi berhasil dihapus.');
    }

    public function pinDiscussion(int $forumId, int $discussionId)
    {
        $this->authorizeAdminOrKoor();
        $this->forumService->pinDiscussion($discussionId);

        return back()->with('success', 'Diskusi berhasil di-pin.');
    }

    public function closeDiscussion(int $forumId, int $discussionId)
    {
        $this->authorizeAdminOrKoor();
        $this->forumService->closeDiscussion($discussionId);

        return back()->with('success', 'Diskusi berhasil ditutup.');
    }

    // Comments (Komentar)

    public function storeComment(Request $request, int $forumId, int $discussionId)
    {
        $request->validate(['isi_comment' => 'required|string']);

        $this->forumService->addComment(Auth::id(), $discussionId, $request->isi_comment);

        return back()->with('success', 'Komentar berhasil ditambahkan.');
    }

    public function updateComment(Request $request, int $commentId)
    {
        $request->validate(['isi_comment' => 'required|string']);

        $this->forumService->updateComment($commentId, Auth::id(), $request->isi_comment);

        return back()->with('success', 'Komentar berhasil diperbarui.');
    }

    public function removeComment(int $commentId)
    {
        $user = Auth::user();
        $roles = $user->roles->pluck('name');
        $isAdminOrKoor = $roles->intersect(['superadmin', 'admin', 'dosen_koordinator'])->isNotEmpty();

        $this->forumService->deleteComment($commentId, $user->id, $isAdminOrKoor);

        return back()->with('success', 'Komentar berhasil dihapus.');
    }

    // Helpers

    private function authorizeAdminOrKoor()
    {
        $roles = Auth::user()->roles->pluck('name');
        if ($roles->intersect(['superadmin', 'admin', 'dosen_koordinator'])->isEmpty()) {
            abort(403, 'Anda tidak memiliki akses (hanya untuk Admin/Dosen Koordinator).');
        }
    }
}
