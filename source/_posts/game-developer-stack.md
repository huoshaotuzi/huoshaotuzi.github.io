---
title: 游戏菜单系统开发之栈的妙用
date: 2020-04-02 11:43:09
tags:
 - 游戏开发
 - 技术
 
categories: 通用技术

description: 栈是一种先进后出的数据结构，本示例讲述在开发《名为怪物的游戏》中巧妙的使用栈解决菜单系统按键冲突问题。

---

## 菜单系统
菜单是所有游戏必不可少的一个系统。

在游戏场景中，按 X 键可以呼出菜单，在菜单中按上下键可以切换菜单选项，再按 Z 键可以选中菜单，然后进入子页面的操作，子页面的菜单中也有子菜单，子菜单的操作与父级菜单一致。当打开菜单的状态，再按 X 键会返回上一级的菜单，直到主菜单返回游戏场景。

演示效果如下：

![名为怪物的游戏 - 游戏菜单](https://s1.ax1x.com/2020/04/02/GGIL5j.gif)


## 实现思路
要实现菜单系统，最关键的地方在于防止按键冲突。

主菜单有按键事件，子菜单中也有按键事件，因此在主菜单中选中了子菜单，就要解除主菜单的监听事件而绑定子菜单的监听事件，多级菜单同理，可以归纳为：打开菜单——解除上一级的监听事件——绑定当前菜单的监听事件。

游戏的例子可能让从未接触过游戏的开发者难以理解，那么再看下一个例子。

![电商菜单](https://s1.ax1x.com/2020/04/02/GGLnsI.png)

上图为某电商首页的 UI，假如产品经理提出一个需求，在这个网页上按 X 键可以展开商品分类的菜单，然后通过键盘的按键可以选择二级菜单，选中一个分类再按 Z 键展开三级菜单，在展开菜单的情况下按 X 键会返回上一级菜单，你应该如何实现此功能？

如果使用正常的方法，用变量来判断打开了哪些菜单，然后再绑定对应的事件，你会发现很难实现无限级的菜单系统，而且业务代码会变得乱糟糟的。

比如情报页面有线索二级菜单，线索菜单又可以进入到线索列表三级菜单，而角色状态可能只有一个显示角色信息的 UI，只有切换角色的按钮，没有三级菜单。

每个主菜单的选项都可能是不同的，它们没有共同点，因此你无法使用继承的关系把相同的操作提取出来，每一个菜单都要单独判断，简直是 `if-else` 地狱，不难想象代码会乱成什么样子。

在这里我们可以用“栈”的特性来优雅的实现菜单系统。

> 栈：一种先进后出的数据结构

栈是一种先进先出的结构，与队列正好相反，而我们打开菜单，按 X 键也正好是逐级向上返回，符合了栈的特性——先打开的菜单最后关闭。

在这里核心点是 **监听事件与解除监听**，无需关心具体的业务逻辑。

> 示例语言为 TypeScript，cocos creator 游戏引擎开发

首先我们定义一个父类 `StackComponent` 这个父类是需要调用栈的组件必须继承的类：

```
// 文件名 Scene_StackComponent.ts

const { ccclass, property } = cc._decorator;

@ccclass
export default abstract class NewClass extends cc.Component {

    /** 添加监听按键 */
    addListener() {
        cc.systemEvent.on(cc.SystemEvent.EventType.KEY_DOWN, this.onKeyDown, this);
        cc.systemEvent.on(cc.SystemEvent.EventType.KEY_UP, this.onKeyUp, this);
    }

    /** 移除监听按键 */
    removeListener() {
        cc.systemEvent.off(cc.SystemEvent.EventType.KEY_DOWN, this.onKeyDown, this);
        cc.systemEvent.off(cc.SystemEvent.EventType.KEY_UP, this.onKeyUp, this);
    }

    abstract onKeyDown(event: cc.Event.EventKeyboard): void;
    abstract onKeyUp(event: cc.Event.EventKeyboard): void;
}

```

`addListener` 方法添加按键监听，而 `removeListener` 方法则移除监听的事件，具体的按键事件进行了抽象，由子类来实现。

- onKeyDown：键盘按下的时候触发
- onKeyUp：键盘弹起的时候触发

接着定义一个栈结构，栈非常简单就可以实现，用一个数组来保存数据，用 `pop` 方法即可弹出最后一个元素：

```
// 文件名 System_StackComponent.ts

const { ccclass, property } = cc._decorator;
import System_StackComponent from "./Scene_StackComponent";

@ccclass
export default class NewClass extends cc.Component {
    private _componentStacks: System_StackComponent[] = [];

    /**
     * 清空栈
     */
    flushStack() {
        this._componentStacks = [];
    }

    /**
     * 从栈取出最后一个元素
     */
    popStack() {
        var len = this._componentStacks.length;
        if (len == 0) {
            cc.error('栈已空，调用失败');
            return;
        }
        // 弹出当前窗口
        let pop = this._componentStacks.pop();
        pop.removeListener();
        // 最后一个元素添加监听
        if (this._componentStacks.length != 0) {
            let last = this._componentStacks[this._componentStacks.length - 1];
            last.addListener();

            cc.log('窗口出栈,剩余：' + this._componentStacks.length);
        }
    }

    /**
     * 菜单组件入栈
     * @param  component 
     */
    pushStack(component: System_StackComponent) {
        if (!component) {
            cc.error('这是一个空的元素');
            return;
        }
        // 原来最后一个元素移除监听
        let len = this._componentStacks.length;
        if (len != 0) {
            let last = this._componentStacks[len - 1];
            last.removeListener();
        }
        // 当前元素添加监听
        component.addListener();

        this._componentStacks.push(component);

        cc.log("入栈：", this._componentStacks)
    }
}

```

`popStack` 方法弹出栈最顶层的元素，并且移除监听事件，同时监听新的顶层元素事件。

`pushStack` 方法将新的菜单入栈，监听当前菜单事件并且移除原来菜单的事件。

栈中所有的元素都继承 `System_StackComponent` 栈组件，因此它们都具有 `addListener` 方法和 `removeListener` 方法。

接下来为了方便，我们把对象保存在 JavaScript 的系统对象 window 中：

```
window["__game"]["stack"] = new System_Stack;
```

这样我们就可以通过 `__game.stack` 来调用栈的方法了。

主菜单脚本如下：

```

const { ccclass, property } = cc._decorator;
import Scene_Menu_Item from "./Scene_Menu_Item";
import Scene_StackComponent from "./Scene_StackComponent";

@ccclass
export default class Scene_Menu extends Scene_StackComponent {

    /** 关闭菜单 */
    closeMenu() {
        // 弹出当前菜单事件
        __game.stack.popStack();
        // 销毁菜单节点（让菜单消失）
        this.node.destroy();
    }

    /** 显示情报面板 */
    showInformationPanel() {
        // 读取情报面板的预制资源
        cc.loader.loadRes("/prefab/SceneInformation", (err, res) => {
            let clueNode = cc.instantiate(res);
            let clue = clueNode.getComponent("Scene_Information");
            // 把菜单脚本压入栈
            __game.stack.pushStack(clue);
            // 调用菜单脚本的初始化方法
            clue.init();
            // 把菜单节点添加到场景（显示菜单 UI）
            cc.find("Scene").addChild(clueNode);
        })
    }
    
    // 具体的监听事件
    onKeyDown(event: cc.Event.EventKeyboard) {
        // 判断当前选项在“情报”菜单，如果此时按 Z 键则调用 showInformationPanel 方法显示情报页
        // 判断按 X 键调用 closeMenu 方法关闭菜单
    }
    
    onKeyUp(event: cc.Event.EventKeyboard) {
        // 业务逻辑忽略
    }
}
```

`closeMenu` 方法关闭当前菜单，并且弹出栈，在所有菜单关闭的时候都调用这个方法。

`onKeyUp` 是键盘弹起事件，暂时不用理会。

在游戏场景加载中，为了能随时随地呼出菜单，我们再定义一个新的类：

```
// System_Menu.ts

const { ccclass, property } = cc._decorator;

import Scene_Menu from "./Scene_Menu";
import Scene_StackComponent from "./Scene_StackComponent";

@ccclass
export default class System_Menu extends Scene_StackComponent {
    private _menuNode: cc.Node = null;

    init() {
        __game.stack.pushStack(this);
    }

    // 监听 X 键
    onKeyDown(event: cc.Event.EventKeyboard) {
        switch (event.keyCode) {
            case cc.macro.KEY.x:
                this.show();
                break;
        }
    }

    onKeyUp(event: cc.Event.EventKeyboard) {
    }

    /** 显示菜单 */
    show() {
        // 加载菜单预制资源并添加到场景
        cc.loader.loadRes("/prefab/Menu", (err, res) => {
            let node = cc.instantiate(res);
            let menu: Scene_Menu = node.getComponent("Scene_Menu");
            // 将主菜单入栈
            __game.stack.pushStack(menu);
            this._menuNode = node;
            // 添加到场景
            cc.find("Scene").addChild(node);
        });
    }
}
```

再将这个类添加到 window 系统对象：

```
// 添加到 window 对象
window["__game"]["menu"] = new System_Menu;
// 执行初始化操作（入栈）
__game.menu.init();
```

如此一来，我们就可以随时随地通过全局的方法 `__game.menu.show()` 调出菜单了！

注意！`System_Menu` 脚本并没有出栈的操作，因为如果这个脚本出栈了，那就不能监听 X 呼出菜单的事件，保留最底层的监听以便随时呼出菜单。

## 知识总结
由于菜单是一级一级往上打开，而关闭的时候是一级一级向下关闭，因此它符合栈的结构，当一个菜单节点入栈时，我们为它绑定监听事件，同时解除上一级菜单的监听事件；当一个菜单出栈时，我们就解除这个菜单的监听事件，然后再给栈新的顶层节点绑定监听事件，无论有多少级的菜单都能够用这种结构来实现，只要让它们继承 `StackComponent` 类即可实现栈的调用控制事件的监听与解除，比起用变量来判断打开了哪些菜单，是不是优雅得多呢？