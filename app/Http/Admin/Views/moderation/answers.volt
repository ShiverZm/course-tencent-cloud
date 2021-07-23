{% extends 'templates/main.volt' %}

{% block content %}

    {{ partial('macros/answer') }}

    <div class="kg-nav">
        <div class="kg-nav-left">
            <span class="layui-breadcrumb">
                <a><cite>回答审核</cite></a>
            </span>
        </div>
    </div>

    <table class="layui-table kg-table layui-form">
        <colgroup>
            <col width="50%">
            <col>
            <col>
            <col width="10%">
        </colgroup>
        <thead>
        <tr>
            <th>回答</th>
            <th>作者</th>
            <th>时间</th>
            <th>操作</th>
        </tr>
        </thead>
        <tbody>
        {% for item in pager.items %}
            {% set question_url = url({'for':'home.question.show','id':item.question.id}) %}
            {% set owner_url = url({'for':'home.user.show','id':item.owner.id}) %}
            {% set moderate_url = url({'for':'admin.answer.moderate','id':item.id}) %}
            <tr>
                <td>
                    <P>问题：<a href="{{ question_url }}" target="_blank">{{ item.question.title }}</a></P>
                    <p class="layui-elip">回答：{{ substr(item.summary,0,32) }}</p>
                </td>
                <td>
                    <p>昵称：<a href="{{ owner_url }}" target="_blank">{{ item.owner.name }}</a></p>
                    <p>编号：{{ item.owner.id }}</p>
                </td>
                <td>{{ date('Y-m-d H:i:s',item.create_time) }}</td>
                <td class="center">
                    <a href="{{ moderate_url }}" class="layui-btn layui-btn-sm">详情</a>
                </td>
            </tr>
        {% endfor %}
        </tbody>
    </table>

    {{ partial('partials/pager') }}

{% endblock %}