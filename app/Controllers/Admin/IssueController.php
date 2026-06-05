<?php
namespace App\Controllers\Admin;
use App\Controllers\BaseController;
use App\Models\CommonIssueModel;

class IssueController extends BaseController
{
    protected CommonIssueModel $model;

    public function __construct()
    {
        $this->model = new CommonIssueModel();
    }

    public function index()
    {
        return view('admin/issues/index', [
            'title'  => 'Manage Common Issues',
            'issues' => $this->model->orderBy('sort_order','ASC')->orderBy('issue_text','ASC')->findAll(),
        ]);
    }

    public function store()
    {
        $text = trim($this->request->getPost('issue_text') ?? '');
        $sort = (int)($this->request->getPost('sort_order') ?? 0);

        if (strlen($text) < 3 || strlen($text) > 300) {
            return redirect()->back()->with('error', 'Issue text must be between 3 and 300 characters.');
        }
        $this->model->insert(['issue_text' => $text, 'sort_order' => $sort, 'is_active' => 1]);
        return redirect()->to(site_url('admin/issues'))->with('success', 'Issue added.');
    }

    public function update($id)
    {
        $id   = (int) $id;
        $text = trim($this->request->getPost('issue_text') ?? '');
        $sort = (int)($this->request->getPost('sort_order') ?? 0);

        if (strlen($text) < 3) return redirect()->back()->with('error', 'Issue text too short.');
        $this->model->update($id, ['issue_text' => $text, 'sort_order' => $sort]);
        return redirect()->to(site_url('admin/issues'))->with('success', 'Issue updated.');
    }

    public function toggle($id)
    {
        $id    = (int) $id;
        $issue = $this->model->find($id);
        if ($issue) $this->model->update($id, ['is_active' => $issue['is_active'] ? 0 : 1]);
        return redirect()->to(site_url('admin/issues'))->with('success', 'Status updated.');
    }

    public function delete($id)
    {
        $id    = (int) $id;
        $issue = $this->model->find($id);
        if (! $issue) {
            return redirect()->to(site_url('admin/issues'))->with('error', 'Issue not found.');
        }
        $this->model->delete($id);
        return redirect()->to(site_url('admin/issues'))->with('success', 'Issue deleted.');
    }
}
