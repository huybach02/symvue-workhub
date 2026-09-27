Please read these files first:
@./.agent/rules/symfony-vue.md
Always response with Vietnamese

# Response presentation

- Luôn bắt đầu mỗi câu trả lời bằng tiêu đề: `## 🤖 AI trả lời`
- Kết thúc mỗi câu trả lời bằng một đường phân cách `---`
- Không đặt toàn bộ câu trả lời trong code block
- Dùng tiêu đề và khoảng cách rõ ràng giữa các phần

# IMPORTANT RULE

1. Think Before Coding
   Don't assume. Don't hide confusion. Surface tradeoffs.

Before implementing:

State your assumptions explicitly. If uncertain, ask.
If multiple interpretations exist, present them - don't pick silently.
If a simpler approach exists, say so. Push back when warranted.
If something is unclear, stop. Name what's confusing. Ask. 2. Simplicity First
Minimum code that solves the problem. Nothing speculative.

No features beyond what was asked.
No abstractions for single-use code.
No "flexibility" or "configurability" that wasn't requested.
No error handling for impossible scenarios.
If you write 200 lines and it could be 50, rewrite it.
Ask yourself: "Would a senior engineer say this is overcomplicated?" If yes, simplify.

3. Surgical Changes
   Touch only what you must. Clean up only your own mess.

When editing existing code:

Don't "improve" adjacent code, comments, or formatting.
Don't refactor things that aren't broken.
Match existing style, even if you'd do it differently.
If you notice unrelated dead code, mention it - don't delete it.
When your changes create orphans:

Remove imports/variables/functions that YOUR changes made unused.
Don't remove pre-existing dead code unless asked.
The test: Every changed line should trace directly to the user's request.

4. Goal-Driven Execution
   Define success criteria. Loop until verified.

Transform tasks into verifiable goals:

"Add validation" → "Write tests for invalid inputs, then make them pass"
"Fix the bug" → "Write a test that reproduces it, then make it pass"
"Refactor X" → "Ensure tests pass before and after"
For multi-step tasks, state a brief plan:

1. [Step] → verify: [check]
2. [Step] → verify: [check]
3. [Step] → verify: [check]
   Strong success criteria let you loop independently. Weak criteria ("make it work") require constant clarification.

# Database & MCP Guidelines

- Khi cần kiểm tra schema bảng, kiểu dữ liệu, index hoặc đối chiếu dữ liệu thực tế để debug, ưu tiên sử dụng MCP server (`dbhub` / `execute_sql`, `search_objects`).
- **NGUYÊN TẮC AN TOÀN**:
    - Chỉ thực thi các truy vấn ĐỌC (`SELECT`, `SHOW`, `EXPLAIN`).
    - TUYỆT ĐỐI KHÔNG tự ý thực thi các truy vấn làm thay đổi cấu trúc hoặc dữ liệu (`INSERT`, `UPDATE`, `DELETE`, `DROP`, `ALTER`, `TRUNCATE`) trừ khi có yêu cầu rõ ràng từ người dùng.
    - Luôn sử dụng `LIMIT` cho các câu lệnh `SELECT` để tránh tải quá nhiều dữ liệu gây quá tải ngữ cảnh.
    - Nếu MCP server chưa được cài đặt thì thông báo để tôi cài đặt nhé

Whenever you read this, please respond with ✅ READED AGENTS.md
